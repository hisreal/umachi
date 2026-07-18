<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use RuntimeException;
use Throwable;

class LeaveManagement extends BaseModel
{
    private const LABELS = ['pending'=>'Pending','forwarded'=>'Forwarded','approved'=>'Approved','rejected'=>'Rejected','cancelled'=>'Cancelled'];
    private const WORKFLOWS = [
        'manager'=>['label'=>'Manager Approves','steps'=>['Employee','Manager','Approved']],
        'supervisor'=>['label'=>'Supervisor Approves','steps'=>['Employee','Supervisor','Approved']],
        'manager_supervisor'=>['label'=>'Manager AND Supervisor','steps'=>['Employee','Supervisor','Manager','Approved']],
        'admin'=>['label'=>'Admin Only','steps'=>['Employee','Admin','Approved']],
        'multi_level'=>['label'=>'Multi-Level Approval','steps'=>['Employee','Supervisor','Manager','Admin','Approved']],
    ];

    public function boot(): void
    {
        $this->ensureSchema();
        $this->seedDefaults();
    }

    public function adminData(): array
    {
        $this->boot();
        $requests = array_map([$this, 'mapRequest'], $this->query($this->requestSql('WHERE lr.deleted_at IS NULL ORDER BY lr.applied_at DESC LIMIT 300')));
        $types = $this->types();
        return [
            'departments'=>array_values(array_unique(array_filter(array_column($requests, 'department')))),
            'roles'=>array_values(array_unique(array_filter(array_column($requests, 'role')))),
            'leaveStatuses'=>['Pending','Approved','Rejected','Forwarded','Cancelled'],
            'leaveTypeNames'=>array_column($types, 'name'),
            'leaveStats'=>$this->stats(),
            'leaveRequests'=>$requests,
            'leaveHistory'=>array_map([$this, 'mapHistory'], $this->query($this->requestSql('WHERE lr.deleted_at IS NULL ORDER BY lr.applied_at DESC LIMIT 500'))),
            'leaveTypes'=>$types,
            'historyStats'=>$this->historyStats(),
            'typeStats'=>$this->typeStats($types),
            'leaveStatusClasses'=>$this->statusClasses(),
            'monthlyLeaveRequests'=>$this->monthlyCounts(),
            'leaveTypeDistribution'=>$this->typeDistribution($types),
            'approvalStatusDistribution'=>[$this->countStatus('pending'),$this->countStatus('approved'),$this->countStatus('rejected'),$this->countStatus('forwarded')],
            'approvalWorkflows'=>self::WORKFLOWS,
            'activeApprovalWorkflow'=>$this->approvalMode(),
            'leaveSuccess'=>Session::pullFlash('leave_success'),
            'leaveError'=>Session::pullFlash('leave_error'),
        ];
    }

    public function employeeData(array $input = []): array
    {
        $this->boot();
        $employee = $this->currentEmployee();
        $types = array_values(array_filter($this->types(), static fn (array $type): bool => $type['status'] === 'Active'));
        $filters = ['search' => mb_substr(trim((string) ($input['search'] ?? '')), 0, 100), 'type' => max(0, (int) ($input['type'] ?? 0)), 'status' => in_array((string) ($input['status'] ?? ''), array_keys(self::LABELS), true) ? (string) $input['status'] : '', 'date_from' => $this->validFilterDate((string) ($input['date_from'] ?? '')), 'date_to' => $this->validFilterDate((string) ($input['date_to'] ?? '')), 'page' => max(1, (int) ($input['page'] ?? 1))];
        $history = [];
        $total = 0;
        $page = 1;
        $pages = 1;
        if ($employee !== null) {
            $where = ['lr.deleted_at IS NULL', 'lr.employee_id = :employee_id'];
            $bindings = ['employee_id' => (int) $employee['db_id']];
            foreach (['type' => 'lr.leave_type_id', 'status' => 'lr.status'] as $key => $column) {
                if ($filters[$key] !== '' && $filters[$key] !== 0) {
                    $where[] = $column . ' = :' . $key;
                    $bindings[$key] = $filters[$key];
                }
            }
            if ($filters['date_from'] !== '') { $where[] = 'lr.start_date >= :date_from'; $bindings['date_from'] = $filters['date_from']; }
            if ($filters['date_to'] !== '') { $where[] = 'lr.end_date <= :date_to'; $bindings['date_to'] = $filters['date_to']; }
            if ($filters['search'] !== '') {
                $where[] = '(lt.name LIKE :search_type OR lr.status LIKE :search_status)';
                $term = '%' . $filters['search'] . '%';
                $bindings['search_type'] = $term;
                $bindings['search_status'] = $term;
            }
            $clause = implode(' AND ', $where);
            $total = (int) $this->database()->value("SELECT COUNT(*) FROM leave_requests lr INNER JOIN leave_types lt ON lt.id = lr.leave_type_id WHERE {$clause}", $bindings);
            $pages = max(1, (int) ceil($total / 8));
            $page = min($filters['page'], $pages);
            $offset = ($page - 1) * 8;
            $history = array_map([$this, 'mapEmployeeHistory'], $this->query($this->requestSql("WHERE {$clause} ORDER BY lr.applied_at DESC LIMIT 8 OFFSET {$offset}"), $bindings));
        }
        return ['employee' => ['db_id' => (int) ($employee['db_id'] ?? 0), 'employee_id' => (string) ($employee['employee_id'] ?? 'N/A'), 'name' => (string) ($employee['name'] ?? 'Employee'), 'department' => (string) ($employee['department'] ?? 'Unassigned'), 'role' => (string) ($employee['role'] ?? 'Staff')], 'leaveTypes' => $types, 'leaveHistory' => $history, 'statusClasses' => $this->statusClasses(), 'filters' => $filters, 'pagination' => ['page' => $page, 'pages' => $pages, 'total' => $total, 'from' => $total === 0 ? 0 : (($page - 1) * 8) + 1, 'to' => min($page * 8, $total)], 'leaveSuccess' => Session::pullFlash('leave_success'), 'leaveError' => Session::pullFlash('leave_error')];
    }
    public function submitRequest(array $data, array $files): void
    {
        $this->boot();
        $employee = $this->requireEmployee();
        $typeId = (int)($data['leave_type_id'] ?? 0);
        $type = $this->queryOne('SELECT * FROM leave_types WHERE id = :id AND status = :status AND deleted_at IS NULL LIMIT 1', ['id'=>$typeId,'status'=>'active']);
        if (!$type) throw new RuntimeException('Select a valid leave type.');
        $start = $this->date((string)($data['start_date'] ?? ''), 'Start date');
        $end = $this->date((string)($data['end_date'] ?? ''), 'End date');
        if ($end < $start) throw new RuntimeException('Start date cannot be after end date.');
        $days = (int)$start->diff($end)->days + 1;
        if ($days <= 0) throw new RuntimeException('Total leave days must be greater than zero.');
        if ((int)$type['max_days_per_year'] > 0 && $days > (int)$type['max_days_per_year']) throw new RuntimeException('Requested days exceed the maximum allowed for this leave type.');
        $reason = trim((string)($data['reason'] ?? ''));
        if ($reason === '') throw new RuntimeException('Reason for leave is required.');
        $overlap = $this->database()->value("SELECT id FROM leave_requests WHERE employee_id = :employee_id AND deleted_at IS NULL AND status IN ('pending','forwarded','approved') AND start_date <= :end_date AND end_date >= :start_date LIMIT 1", ['employee_id'=>(int)$employee['db_id'],'start_date'=>$start->format('Y-m-d'),'end_date'=>$end->format('Y-m-d')]);
        if ($overlap !== null) throw new RuntimeException('You already have a leave request that overlaps with the selected dates.');
        $attachment = $this->storeAttachment($files['supporting_document'] ?? null, (bool)$type['requires_attachment']);
        $this->transaction(function (Database $database) use ($employee, $typeId, $start, $end, $days, $reason, $attachment): void {
            $id = (int)$database->insert('leave_requests', ['request_code'=>$this->code(),'employee_id'=>(int)$employee['db_id'],'leave_type_id'=>$typeId,'workflow_id'=>$this->workflowId(),'reason'=>$reason,'start_date'=>$start->format('Y-m-d'),'end_date'=>$end->format('Y-m-d'),'total_days'=>$days,'applied_at'=>date('Y-m-d H:i:s'),'current_stage'=>$this->initialStage($this->approvalMode()),'status'=>'pending']);
            if ($attachment) $database->insert('leave_request_attachments', ['leave_request_id'=>$id,'file_path'=>$attachment['path'],'original_name'=>$attachment['name'],'uploaded_by'=>$this->userId()]);
            $this->recordAction($database, $id, 'submitted', null, 'pending', 'Leave request submitted.');
            $this->log('Leave Submitted', $id, null, ['status'=>'pending']);
        });
    }

    public function cancelOwnRequest(int $id): void
    {
        $this->boot();
        $employee = $this->requireEmployee();
        $request = $this->queryOne('SELECT * FROM leave_requests WHERE id = :id AND employee_id = :employee_id AND deleted_at IS NULL LIMIT 1', ['id' => $id, 'employee_id' => (int) $employee['db_id']]);
        if ($request === null) {
            throw new RuntimeException('Leave request was not found.');
        }
        if (!in_array((string) $request['status'], ['pending', 'forwarded'], true)) {
            throw new RuntimeException('Only pending leave requests can be cancelled.');
        }
        $this->transaction(function (Database $database) use ($request, $id): void {
            $database->update('leave_requests', ['status' => 'cancelled', 'current_stage' => 'Cancelled'], ['id' => $id]);
            $this->recordAction($database, $id, 'cancelled', (string) $request['status'], 'cancelled', 'Cancelled by employee.');
            $this->log('Leave Cancelled', $id, ['status' => $request['status']], ['status' => 'cancelled']);
        });
    }
    public function processRequest(int $id, string $action, string $comments = ''): void
    {
        $this->boot();
        $request = $this->queryOne('SELECT * FROM leave_requests WHERE id = :id AND deleted_at IS NULL LIMIT 1', ['id'=>$id]);
        if (!$request) throw new RuntimeException('Leave request was not found.');
        if (in_array((string)$request['status'], ['approved','rejected','cancelled'], true)) throw new RuntimeException('This leave request has already been finalized.');
        $from = (string)$request['status'];
        $mode = $this->approvalMode();
        $stage = (string)($request['current_stage'] ?? '');
        $this->ensureActiveApproverCanProcessStage($stage);
        $status = match ($action) {'approve'=>$this->isFinal($mode,$stage)?'approved':'forwarded','forward'=>'forwarded','reject'=>'rejected',default=>throw new RuntimeException('Select a valid approval action.')};
        $newStage = $status === 'approved' ? 'Completed' : ($status === 'rejected' ? 'Rejected' : $this->nextStage($mode, $stage));
        $this->transaction(function (Database $database) use ($request, $id, $action, $comments, $from, $status, $newStage): void {
            $database->update('leave_requests', ['status'=>$status,'current_stage'=>$newStage,'final_approved_by'=>$status==='approved'?$this->userId():$request['final_approved_by'],'final_approved_at'=>$status==='approved'?date('Y-m-d H:i:s'):$request['final_approved_at'],'notes'=>$comments !== '' ? $comments : $request['notes']], ['id'=>$id]);
            $this->recordAction($database, $id, $action === 'reject' ? 'rejected' : ($status === 'approved' ? 'approved' : 'forwarded'), $from, $status, $comments);
            if ($status === 'approved') $this->markAttendanceOnLeave($database, $request);
            $this->log('Leave '.self::LABELS[$status], $id, ['status'=>$from], ['status'=>$status]);
        });
    }

    public function saveType(array $data): void
    {
        $this->boot();
        $name = trim((string)($data['leave_name'] ?? ''));
        if ($name === '') throw new RuntimeException('Leave name is required.');
        if ($this->database()->value('SELECT id FROM leave_types WHERE name = :name AND deleted_at IS NULL LIMIT 1', ['name'=>$name]) !== null) throw new RuntimeException('A leave type with this name already exists.');
        $id = (int)$this->insert('leave_types', ['name'=>$name,'description'=>trim((string)($data['description'] ?? '')),'max_days_per_year'=>max(1,(int)($data['maximum_days'] ?? 1)),'is_paid'=>isset($data['is_paid'])?1:0,'requires_attachment'=>isset($data['requires_attachment'])?1:0,'status'=>strtolower((string)($data['status'] ?? 'active')) === 'inactive' ? 'inactive' : 'active']);
        $this->log('Leave Type Added', $id, null, ['name'=>$name]);
    }

    public function toggleType(int $id): void
    {
        $this->boot();
        $type = $this->queryOne('SELECT * FROM leave_types WHERE id = :id AND deleted_at IS NULL LIMIT 1', ['id'=>$id]);
        if (!$type) throw new RuntimeException('Leave type was not found.');
        $status = $type['status'] === 'active' ? 'inactive' : 'active';
        $this->update('leave_types', ['status'=>$status], ['id'=>$id]);
        $this->log('Leave Type Updated', $id, ['status'=>$type['status']], ['status'=>$status]);
    }

    public function saveSettings(array $data): void
    {
        $this->boot();
        $mode = (string)($data['approvalWorkflow'] ?? 'multi_level');
        if (!isset(self::WORKFLOWS[$mode])) throw new RuntimeException('Select a valid approval workflow.');
        $this->database()->execute('INSERT INTO leave_approval_settings (id, approval_mode, updated_by, updated_at) VALUES (1, :mode, :user, NOW()) ON DUPLICATE KEY UPDATE approval_mode = VALUES(approval_mode), updated_by = VALUES(updated_by), updated_at = NOW()', ['mode'=>$mode,'user'=>$this->userId()]);
        $this->workflowId($mode);
        $this->log('Approval Setting Changed', 1, null, ['approval_mode'=>$mode]);
    }
    private function ensureSchema(): void
    {
        $this->database()->execute("CREATE TABLE IF NOT EXISTS leave_types (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, name VARCHAR(100) NOT NULL, description TEXT NULL, max_days_per_year SMALLINT UNSIGNED NOT NULL DEFAULT 0, is_paid TINYINT(1) NOT NULL DEFAULT 1, requires_attachment TINYINT(1) NOT NULL DEFAULT 0, status ENUM('active','inactive') NOT NULL DEFAULT 'active', created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, deleted_at TIMESTAMP NULL DEFAULT NULL, PRIMARY KEY (id), UNIQUE KEY uq_leave_types_name (name), KEY idx_leave_types_status (status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->database()->execute("CREATE TABLE IF NOT EXISTS leave_requests (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, request_code VARCHAR(60) NOT NULL, employee_id BIGINT UNSIGNED NOT NULL, leave_type_id BIGINT UNSIGNED NOT NULL, workflow_id BIGINT UNSIGNED NULL, reason TEXT NOT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, total_days DECIMAL(6,2) NOT NULL, applied_at DATETIME NOT NULL, current_stage VARCHAR(120) NULL, status ENUM('pending','forwarded','approved','rejected','cancelled') NOT NULL DEFAULT 'pending', final_approved_by BIGINT UNSIGNED NULL, final_approved_at DATETIME NULL, notes TEXT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, deleted_at TIMESTAMP NULL DEFAULT NULL, PRIMARY KEY (id), UNIQUE KEY uq_leave_requests_code (request_code), KEY idx_leave_requests_employee (employee_id), KEY idx_leave_requests_type (leave_type_id), KEY idx_leave_requests_status (status, applied_at), KEY idx_leave_requests_dates (start_date, end_date)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->database()->execute("CREATE TABLE IF NOT EXISTS leave_request_actions (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, leave_request_id BIGINT UNSIGNED NOT NULL, step_id BIGINT UNSIGNED NULL, actor_user_id BIGINT UNSIGNED NULL, action ENUM('submitted','forwarded','approved','rejected','cancelled','commented') NOT NULL, from_status VARCHAR(40) NULL, to_status VARCHAR(40) NULL, comments TEXT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (id), KEY idx_leave_actions_request (leave_request_id, created_at), KEY idx_leave_actions_actor (actor_user_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->database()->execute("CREATE TABLE IF NOT EXISTS leave_request_attachments (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, leave_request_id BIGINT UNSIGNED NOT NULL, file_path VARCHAR(255) NOT NULL, original_name VARCHAR(180) NULL, uploaded_by BIGINT UNSIGNED NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (id), KEY idx_leave_attachments_request (leave_request_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->database()->execute("CREATE TABLE IF NOT EXISTS leave_approval_settings (id BIGINT UNSIGNED NOT NULL, approval_mode ENUM('manager','supervisor','manager_supervisor','admin','multi_level') NOT NULL DEFAULT 'multi_level', updated_by BIGINT UNSIGNED NULL, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->database()->execute("CREATE TABLE IF NOT EXISTS leave_approval_workflows (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, name VARCHAR(120) NOT NULL, slug VARCHAR(120) NOT NULL, description TEXT NULL, status ENUM('active','inactive') NOT NULL DEFAULT 'active', created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (id), UNIQUE KEY uq_leave_workflows_slug (slug)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    private function seedDefaults(): void
    {
        foreach ([['Annual Leave','Paid yearly vacation leave for eligible staff.',21,0],['Sick Leave','Medical leave supported by a health note when required.',14,1],['Casual Leave','Short personal leave for urgent non-medical reasons.',5,0],['Emergency Leave','Urgent leave for sudden family or personal emergencies.',3,0],['Maternity Leave','Maternity leave for eligible employees.',90,1],['Paternity Leave','Paternity leave for eligible employees.',14,0],['Study Leave','Approved leave for training and professional development.',10,1],['Compassionate Leave','Leave granted for bereavement or serious family events.',5,0]] as [$name,$description,$max,$attachment]) {
            if ($this->database()->value('SELECT id FROM leave_types WHERE name = :name LIMIT 1', ['name'=>$name]) === null) $this->insert('leave_types', ['name'=>$name,'description'=>$description,'max_days_per_year'=>$max,'is_paid'=>1,'requires_attachment'=>$attachment,'status'=>'active']);
        }
        if ($this->database()->value('SELECT id FROM leave_approval_settings WHERE id = 1') === null) $this->database()->execute("INSERT INTO leave_approval_settings (id, approval_mode, updated_by) VALUES (1, 'multi_level', :user)", ['user'=>$this->userId()]);
        foreach (array_keys(self::WORKFLOWS) as $mode) $this->workflowId($mode);
    }

    private function requestSql(string $suffix): string
    {
        return "SELECT lr.*, lt.name AS leave_type, lt.requires_attachment, e.employee_code, CONCAT(e.first_name, ' ', e.last_name) AS employee_name, d.name AS department_name, jt.name AS role_name, u.username AS final_approver, (SELECT file_path FROM leave_request_attachments lra WHERE lra.leave_request_id = lr.id ORDER BY lra.id DESC LIMIT 1) AS attachment_path FROM leave_requests lr INNER JOIN leave_types lt ON lt.id = lr.leave_type_id INNER JOIN employees e ON e.id = lr.employee_id LEFT JOIN departments d ON d.id = e.department_id LEFT JOIN job_titles jt ON jt.id = e.job_title_id LEFT JOIN users u ON u.id = lr.final_approved_by {$suffix}";
    }

    private function mapRequest(array $row): array
    {
        $status = self::LABELS[(string)$row['status']] ?? 'Pending';
        return ['id'=>(int)$row['id'],'employee_id'=>(string)$row['employee_code'],'employee'=>(string)$row['employee_name'],'department'=>(string)($row['department_name']??'Unassigned'),'role'=>(string)($row['role_name']??'Staff'),'type'=>(string)$row['leave_type'],'reason'=>(string)$row['reason'],'start'=>(string)$row['start_date'],'end'=>(string)$row['end_date'],'days'=>(int)$row['total_days'],'applied'=>date('Y-m-d', strtotime((string)$row['applied_at'])),'stage'=>(string)($row['current_stage']??'Review'),'status'=>$status,'documents'=>empty($row['attachment_path'])?'None':basename((string)$row['attachment_path']),'history'=>$this->actionHistory((int)$row['id']),'notes'=>(string)($row['notes']??'')];
    }

    private function mapHistory(array $row): array
    {
        return ['employee'=>(string)$row['employee_name'],'department'=>(string)($row['department_name']??'Unassigned'),'type'=>(string)$row['leave_type'],'start'=>(string)$row['start_date'],'end'=>(string)$row['end_date'],'days'=>(int)$row['total_days'],'approved_by'=>(string)($row['final_approver']??'-'),'status'=>self::LABELS[(string)$row['status']] ?? 'Pending'];
    }

    private function mapEmployeeHistory(array $row): array
    {
        return ['db_id'=>(int)$row['id'],'request_id'=>(string)$row['request_code'],'leave_type'=>(string)$row['leave_type'],'start_date'=>(string)$row['start_date'],'end_date'=>(string)$row['end_date'],'days'=>(int)$row['total_days'],'date_applied'=>date('Y-m-d', strtotime((string)$row['applied_at'])),'status'=>self::LABELS[(string)$row['status']] ?? 'Pending','approved_by'=>(string)($row['final_approver']??'-'),'stage'=>(string)($row['current_stage']??'Waiting for Review'),'remarks'=>(string)($row['notes']??'')];
    }

    private function types(): array
    {
        return array_map(static fn(array $row): array => ['id'=>(int)$row['id'],'name'=>(string)$row['name'],'description'=>(string)($row['description']??''),'max_days'=>(int)$row['max_days_per_year'],'paid'=>(bool)$row['is_paid'],'requires_attachment'=>(bool)$row['requires_attachment'],'status'=>$row['status']==='active'?'Active':'Inactive'], $this->query('SELECT * FROM leave_types WHERE deleted_at IS NULL ORDER BY name'));
    }

    private function stats(): array
    {
        return [
            ['label' => 'Pending Requests', 'value' => $this->countStatus('pending'), 'icon' => 'fa-solid fa-hourglass-half', 'tone' => 'warning'],
            ['label' => 'Approved Leaves', 'value' => $this->countStatus('approved'), 'icon' => 'fa-solid fa-circle-check', 'tone' => 'success'],
            ['label' => 'Rejected Leaves', 'value' => $this->countStatus('rejected'), 'icon' => 'fa-solid fa-circle-xmark', 'tone' => 'danger'],
            ['label' => 'Forwarded Requests', 'value' => $this->countStatus('forwarded'), 'icon' => 'fa-solid fa-share', 'tone' => 'info'],
        ];
    }

    private function validFilterDate(string $value): string
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
        return $date instanceof \DateTimeImmutable && $date->format('Y-m-d') === $value ? $value : '';
    }
    private function historyStats(): array
    {
        $days = (float) ($this->database()->value("SELECT COALESCE(SUM(total_days), 0) FROM leave_requests WHERE deleted_at IS NULL AND status = 'approved'") ?? 0);

        return [
            ['label' => 'Total Leave Records', 'value' => (int) ($this->database()->value('SELECT COUNT(*) FROM leave_requests WHERE deleted_at IS NULL') ?? 0), 'icon' => 'fa-solid fa-folder-open', 'tone' => 'primary'],
            ['label' => 'Approved Days', 'value' => (int) $days, 'icon' => 'fa-solid fa-circle-check', 'tone' => 'success'],
            ['label' => 'Pending Review', 'value' => $this->countStatus('pending') + $this->countStatus('forwarded'), 'icon' => 'fa-solid fa-hourglass-half', 'tone' => 'warning'],
            ['label' => 'Rejected', 'value' => $this->countStatus('rejected'), 'icon' => 'fa-solid fa-ban', 'tone' => 'danger'],
        ];
    }

    private function typeStats(array $types): array
    {
        return [
            ['label' => 'Total Leave Types', 'value' => count($types), 'icon' => 'fa-solid fa-list-check', 'tone' => 'primary'],
            ['label' => 'Active Leave Types', 'value' => count(array_filter($types, static fn(array $type): bool => $type['status'] === 'Active')), 'icon' => 'fa-solid fa-toggle-on', 'tone' => 'success'],
            ['label' => 'Inactive Leave Types', 'value' => count(array_filter($types, static fn(array $type): bool => $type['status'] === 'Inactive')), 'icon' => 'fa-solid fa-toggle-off', 'tone' => 'warning'],
        ];
    }

    private function statusClasses(): array
    {
        return [
            'Pending' => 'leave-status--pending',
            'Forwarded' => 'leave-status--forwarded',
            'Approved' => 'leave-status--approved',
            'Rejected' => 'leave-status--rejected',
            'Cancelled' => 'leave-status--cancelled',
            'Active' => 'leave-status--approved',
            'Inactive' => 'leave-status--cancelled',
        ];
    }

    private function countStatus(?string $status): int
    {
        if ($status === null) {
            return (int) ($this->database()->value('SELECT COUNT(*) FROM leave_requests WHERE deleted_at IS NULL') ?? 0);
        }

        return (int) ($this->database()->value('SELECT COUNT(*) FROM leave_requests WHERE deleted_at IS NULL AND status = :status', ['status' => $status]) ?? 0);
    }

    private function monthlyCounts(): array
    {
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $key = date('Y-m', strtotime("-{$i} months"));
            $months[$key] = 0;
        }

        $rows = $this->query("SELECT DATE_FORMAT(applied_at, '%Y-%m') AS month_key, COUNT(*) AS total FROM leave_requests WHERE deleted_at IS NULL AND applied_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY month_key");
        foreach ($rows as $row) {
            $key = (string) $row['month_key'];
            if (array_key_exists($key, $months)) {
                $months[$key] = (int) $row['total'];
            }
        }

        return array_values($months);
    }

    private function typeDistribution(array $types): array
    {
        $counts = array_fill_keys(array_column($types, 'name'), 0);
        $rows = $this->query('SELECT lt.name, COUNT(lr.id) AS total FROM leave_types lt LEFT JOIN leave_requests lr ON lr.leave_type_id = lt.id AND lr.deleted_at IS NULL WHERE lt.deleted_at IS NULL GROUP BY lt.id, lt.name ORDER BY lt.name');

        foreach ($rows as $row) {
            $counts[(string) $row['name']] = (int) $row['total'];
        }

        return array_values($counts);
    }

    private function actionHistory(int $requestId): array
    {
        $rows = $this->query(
            "SELECT lra.*, COALESCE(u.username, 'System') AS actor FROM leave_request_actions lra LEFT JOIN users u ON u.id = lra.actor_user_id WHERE lra.leave_request_id = :id ORDER BY lra.created_at",
            ['id' => $requestId]
        );

        return array_map(static fn(array $row): array => [
            'stage' => ucfirst((string) $row['action']),
            'actor' => (string) $row['actor'],
            'status' => ucfirst((string) ($row['to_status'] ?? $row['action'])),
            'date' => (string) $row['created_at'],
            'comment' => (string) ($row['comments'] ?? ''),
        ], $rows);
    }

    private function approvalMode(): string
    {
        $mode = (string) ($this->database()->value('SELECT approval_mode FROM leave_approval_settings WHERE id = 1') ?? 'multi_level');

        return isset(self::WORKFLOWS[$mode]) ? $mode : 'multi_level';
    }

    private function workflowId(?string $mode = null): ?int
    {
        $mode ??= $this->approvalMode();
        if (!isset(self::WORKFLOWS[$mode])) {
            return null;
        }

        $id = $this->database()->value('SELECT id FROM leave_approval_workflows WHERE slug = :slug LIMIT 1', ['slug' => $mode]);
        if ($id !== null) {
            return (int) $id;
        }

        return (int) $this->insert('leave_approval_workflows', [
            'name' => self::WORKFLOWS[$mode]['label'],
            'slug' => $mode,
            'description' => implode(' > ', self::WORKFLOWS[$mode]['steps']),
            'status' => 'active',
        ]);
    }

    private function initialStage(string $mode): string
    {
        return match ($mode) {
            'manager' => 'Manager Review',
            'supervisor', 'manager_supervisor', 'multi_level' => 'Supervisor Review',
            'admin' => 'Admin Review',
            default => 'Supervisor Review',
        };
    }

    private function nextStage(string $mode, string $stage): string
    {
        return match ($mode) {
            'manager_supervisor' => str_contains($stage, 'Supervisor') ? 'Manager Review' : 'Completed',
            'multi_level' => str_contains($stage, 'Supervisor') ? 'Manager Review' : (str_contains($stage, 'Manager') ? 'Admin Review' : 'Completed'),
            default => 'Completed',
        };
    }

    private function isFinal(string $mode, string $stage): bool
    {
        return match ($mode) {
            'manager', 'supervisor', 'admin' => true,
            'manager_supervisor' => str_contains($stage, 'Manager'),
            'multi_level' => str_contains($stage, 'Admin'),
            default => false,
        };
    }

    private function ensureActiveApproverCanProcessStage(string $stage): void
    {
        $displayRole = strtolower(trim((string) Session::get('auth.role', '')));
        $normalizedStage = strtolower($stage);

        if ($displayRole === 'supervisor' && !str_contains($normalizedStage, 'supervisor')) {
            throw new RuntimeException('This leave request is not currently awaiting Supervisor approval.');
        }

        if ($displayRole === 'manager' && !str_contains($normalizedStage, 'manager')) {
            throw new RuntimeException('This leave request is not currently awaiting Manager approval.');
        }
    }

    private function currentEmployee(): ?array
    {
        $employeeId = Session::get('auth.employee_id');
        $userId = Session::get('auth.user_id');
        $bindings = [];
        $where = '1 = 0';

        if ($employeeId !== null && (int) $employeeId > 0) {
            $where = 'e.id = :employee_id';
            $bindings['employee_id'] = (int) $employeeId;
        } elseif ($userId !== null && (int) $userId > 0) {
            $where = 'u.id = :user_id';
            $bindings['user_id'] = (int) $userId;
        }

        return $this->queryOne(
            "SELECT e.id AS db_id, e.employee_code AS employee_id, CONCAT(e.first_name, ' ', e.last_name) AS name, d.name AS department, jt.name AS role FROM employees e LEFT JOIN users u ON u.employee_id = e.id LEFT JOIN departments d ON d.id = e.department_id LEFT JOIN job_titles jt ON jt.id = e.job_title_id WHERE {$where} LIMIT 1",
            $bindings
        );
    }

    private function requireEmployee(): array
    {
        $employee = $this->currentEmployee();
        if (!$employee || (int) $employee['db_id'] <= 0) {
            throw new RuntimeException('Your employee profile could not be found. Please contact the administrator.');
        }

        return $employee;
    }

    private function date(string $value, string $label): \DateTimeImmutable
    {
        $value = trim($value);
        if ($value === '') {
            throw new RuntimeException("{$label} is required.");
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) {
            throw new RuntimeException("{$label} is invalid.");
        }

        return $date;
    }

    private function storeAttachment(?array $file, bool $required): ?array
    {
        if (!$file || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            if ($required) {
                throw new RuntimeException('A supporting document is required for this leave type.');
            }

            return null;
        }

        if ((int) $file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('The supporting document could not be uploaded.');
        }

        if ((int) $file['size'] > 5 * 1024 * 1024) {
            throw new RuntimeException('Supporting document must not exceed 5MB.');
        }

        $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
        if (!in_array($extension, $allowed, true)) {
            throw new RuntimeException('Upload a PDF, image, or Word document only.');
        }

        $directory = BASE_PATH . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'leave-documents';
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Leave document upload directory could not be created.');
        }

        $filename = 'leave_' . date('YmdHis') . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
        $target = $directory . DIRECTORY_SEPARATOR . $filename;
        if (!move_uploaded_file((string) $file['tmp_name'], $target)) {
            throw new RuntimeException('Supporting document could not be saved.');
        }

        return ['path' => 'uploads/leave-documents/' . $filename, 'name' => (string) $file['name']];
    }

    private function recordAction(Database $database, int $requestId, string $action, ?string $from, ?string $to, string $comments): void
    {
        $database->insert('leave_request_actions', [
            'leave_request_id' => $requestId,
            'step_id' => null,
            'actor_user_id' => $this->userId(),
            'action' => $action,
            'from_status' => $from,
            'to_status' => $to,
            'comments' => $comments,
        ]);
    }

    private function markAttendanceOnLeave(Database $database, array $request): void
    {
        $start = new \DateTimeImmutable((string) $request['start_date']);
        $end = new \DateTimeImmutable((string) $request['end_date']);

        for ($date = $start; $date <= $end; $date = $date->modify('+1 day')) {
            $day = $date->format('Y-m-d');
            $existing = $database->selectOne('SELECT id FROM attendance WHERE employee_id = :employee_id AND attendance_date = :attendance_date LIMIT 1', [
                'employee_id' => (int) $request['employee_id'],
                'attendance_date' => $day,
            ]);

            if ($existing) {
                $database->update('attendance', [
                    'attendance_status' => 'On Leave',
                    'verification_status' => 'Verified',
                    'remarks' => 'Approved leave request #' . $request['request_code'],
                ], ['id' => (int) $existing['id']]);
                continue;
            }

            $database->insert('attendance', [
                'employee_id' => (int) $request['employee_id'],
                'shift_id' => null,
                'attendance_date' => $day,
                'attendance_status' => 'On Leave',
                'verification_status' => 'Verified',
                'remarks' => 'Approved leave request #' . $request['request_code'],
            ]);
        }
    }

    private function userId(): ?int
    {
        $userId = Session::get('auth.user_id');

        return $userId !== null ? (int) $userId : null;
    }

    private function code(): string
    {
        do {
            $code = 'LV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        } while ($this->database()->value('SELECT id FROM leave_requests WHERE request_code = :code LIMIT 1', ['code' => $code]) !== null);

        return $code;
    }

    private function log(string $action, ?int $recordId, ?array $oldValues, ?array $newValues): void
    {
        try {
            $request = Request::capture();
            $this->database()->insert('activity_logs', [
                'log_code' => 'ACT-' . date('YmdHis') . '-' . random_int(100, 999),
                'user_id' => $this->userId(),
                'employee_id' => ($employeeId = (int) Session::get('auth.employee_id', 0)) > 0 ? $employeeId : null,
                'activity_type' => $action,
                'module' => 'Leave Management',
                'activity' => $action,
                'entity_type' => 'leave_request',
                'entity_id' => $recordId,
                'old_value' => $oldValues ? json_encode($oldValues, JSON_THROW_ON_ERROR) : null,
                'new_value' => $newValues ? json_encode($newValues, JSON_THROW_ON_ERROR) : null,
                'ip_address' => $request->ip(),
                'browser' => substr($request->userAgent(), 0, 120),
                'status' => 'success',
                'notes' => 'Role: ' . (string) Session::get('auth.role', 'Unknown'),
            ]);
        } catch (Throwable $exception) {
            error_log('[LeaveManagement] Activity log failed: ' . $exception->getMessage());
        }
    }
}






