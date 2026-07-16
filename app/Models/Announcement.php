<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Session;
use DateTimeImmutable;
use RuntimeException;
use Throwable;

class Announcement extends BaseModel
{
    private const ROLES = ['Admin', 'Manager', 'Supervisor', 'Pump Attendant', 'Cashier', 'Security', 'Driver', 'Accountant'];
    private const PRIORITIES = ['Low', 'Normal', 'High', 'Urgent'];
    private const STATUSES = ['Draft', 'Published', 'Archived'];

    public function boot(): void
    {
        $this->ensureSchema();
        $this->ensureRoles();
    }

    public function adminData(?int $selectedId = null): array
    {
        $this->boot();
        $announcements = array_map([$this, 'mapRow'], $this->query($this->baseSql() . ' ORDER BY a.created_at DESC, a.id DESC'));
        $selected = $this->findForView($selectedId) ?? ($announcements[0] ?? $this->emptyAnnouncement());

        return [
            'announcements' => $announcements,
            'categories' => ['General Notice', 'Staff Meeting', 'Safety Notice', 'Maintenance', 'Company Policy', 'Emergency', 'Holiday', 'Training', 'Promotion', 'Other'],
            'priorities' => self::PRIORITIES,
            'statuses' => ['Draft', 'Published', 'Scheduled', 'Expired', 'Archived'],
            'audienceGroups' => array_merge(['Everyone'], self::ROLES),
            'notificationGroups' => ['Notify All Employees', 'Notify Managers', 'Notify Supervisors'],
            'announcementStats' => $this->readStats($selected['db_id'] ?? null),
            'summaryCards' => $this->summaryCards(),
            'selectedAnnouncement' => $selected,
            'announcementContent' => '<p>' . nl2br(e((string) ($selected['message'] ?? ''))) . '</p>',
            'announcementSuccess' => Session::pullFlash('announcement_success'),
            'announcementError' => Session::pullFlash('announcement_error'),
        ];
    }

    public function dashboardAnnouncements(?string $role = null, int $limit = 5): array
    {
        $this->boot();
        $role = trim((string) ($role ?? Session::get('auth.role', '')));
        $bindings = ['now_start' => date('Y-m-d H:i:s'), 'now_end' => date('Y-m-d H:i:s')];
        $roleFilter = '';

        if ($role !== '' && strtolower($role) !== 'admin') {
            $roleFilter = "AND (a.visibility = 'All' OR EXISTS (SELECT 1 FROM announcement_roles ar INNER JOIN roles r ON r.id = ar.role_id WHERE ar.announcement_id = a.id AND r.name = :role))";
            $bindings['role'] = $role;
        }

        $bindings['limit'] = $limit;
        $rows = $this->query(
            "SELECT a.* FROM announcements a WHERE a.status = 'Published' AND a.publish_date <= :now_start AND (a.expiry_date IS NULL OR a.expiry_date >= :now_end) {$roleFilter} ORDER BY a.priority = 'Urgent' DESC, a.publish_date DESC LIMIT :limit",
            $bindings
        );

        return array_map(static fn(array $row): array => [
            'id' => (int) $row['id'],
            'title' => (string) $row['title'],
            'message' => (string) $row['message'],
            'date' => substr((string) $row['publish_date'], 0, 10),
            'icon' => 'fa-solid fa-bullhorn',
            'priority' => (string) $row['priority'],
        ], $rows);
    }

    public function store(array $data): int
    {
        $this->boot();
        $payload = $this->payload($data);
        $this->guardDuplicate($payload['title'], $payload['publish_date']);

        return (int) $this->transaction(function (Database $database) use ($payload): int {
            $roles = $payload['roles'];
            unset($payload['roles']);
            $payload['created_by'] = $this->currentUserId();
            $id = (int) $database->insert('announcements', $payload);
            $this->syncRoles($database, $id, $roles);
            $this->log('Announcement Created', $id, null, $payload);

            return $id;
        });
    }

    public function updateAnnouncement(int $id, array $data): void
    {
        $this->boot();
        $existing = $this->findRaw($id);
        if (!$existing) {
            throw new RuntimeException('Announcement was not found.');
        }

        $payload = $this->payload($data, false);
        $this->guardDuplicate($payload['title'], $payload['publish_date'], $id);

        $this->transaction(function (Database $database) use ($id, $payload, $existing): void {
            $roles = $payload['roles'];
            unset($payload['roles']);
            $payload['updated_by'] = $this->currentUserId();
            $database->update('announcements', $payload, ['id' => $id]);
            $this->syncRoles($database, $id, $roles);
            $this->log('Announcement Updated', $id, $existing, $payload);
        });
    }

    public function changeStatus(int $id, string $action): void
    {
        $this->boot();
        $existing = $this->findRaw($id);
        if (!$existing) {
            throw new RuntimeException('Announcement was not found.');
        }

        $status = match ($action) {
            'publish' => 'Published',
            'archive', 'delete' => 'Archived',
            default => throw new RuntimeException('Select a valid announcement action.'),
        };

        $this->update('announcements', ['status' => $status, 'updated_by' => $this->currentUserId()], ['id' => $id]);
        $this->log('Announcement ' . $status, $id, ['status' => $existing['status']], ['status' => $status]);
    }

    private function payload(array $data, bool $creating = true): array
    {
        $title = trim((string) ($data['title'] ?? ''));
        $message = trim((string) ($data['message'] ?? $data['content'] ?? ''));
        $priority = (string) ($data['priority'] ?? 'Normal');
        $priority = $priority === 'Medium' ? 'Normal' : $priority;
        $category = trim((string) ($data['category'] ?? 'General Notice'));
        $status = (string) ($data['status'] ?? ($data['announcement_status'] ?? 'Draft'));
        $status = match ($status) {
            'Publish Immediately', 'Schedule', 'Published' => 'Published',
            'Archived' => 'Archived',
            default => 'Draft',
        };

        if ($title === '') {
            throw new RuntimeException('Announcement title is required.');
        }
        if ($message === '') {
            throw new RuntimeException('Announcement message is required.');
        }
        if (!in_array($priority, self::PRIORITIES, true)) {
            throw new RuntimeException('Select a valid priority.');
        }

        $publishDate = $this->dateTime((string) ($data['publish_date'] ?? ''), (string) ($data['publish_time'] ?? '00:00'), 'Publish date');
        $expiryDate = null;
        if (trim((string) ($data['expiry_date'] ?? '')) !== '') {
            $expiryDate = $this->dateTime((string) $data['expiry_date'], '23:59', 'Expiry date');
            if ($expiryDate < $publishDate) {
                throw new RuntimeException('Expiry date cannot be earlier than publish date.');
            }
        }

        $roles = array_values(array_filter(array_map('trim', (array) ($data['role_names'] ?? $data['audience'] ?? []))));
        $visibility = in_array('Everyone', $roles, true) || in_array('All', $roles, true) || $roles === [] ? 'All' : 'Selected Roles';
        if ($visibility === 'Selected Roles') {
            $roles = array_values(array_intersect($roles, self::ROLES));
            if ($roles === []) {
                throw new RuntimeException('Select at least one target role.');
            }
        } else {
            $roles = [];
        }

        return [
            'title' => $title,
            'message' => $message,
            'slug' => $this->uniqueSlug($title, isset($data['announcement_id']) ? (int) $data['announcement_id'] : null),
            'category' => $category !== '' ? $category : 'General Notice',
            'priority' => $priority,
            'visibility' => $visibility,
            'publish_date' => $publishDate->format('Y-m-d H:i:s'),
            'expiry_date' => $expiryDate?->format('Y-m-d H:i:s'),
            'status' => $status,
            'is_pinned' => isset($data['is_pinned']) ? 1 : 0,
            'roles' => $roles,
        ];
    }

    private function ensureSchema(): void
    {
        $this->database()->execute("CREATE TABLE IF NOT EXISTS announcements (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, title VARCHAR(255) NOT NULL, message TEXT NOT NULL, category VARCHAR(120) NOT NULL DEFAULT 'General Notice', priority ENUM('Low','Normal','High','Urgent') NOT NULL DEFAULT 'Normal', visibility ENUM('All','Selected Roles') NOT NULL DEFAULT 'All', publish_date DATETIME NOT NULL, expiry_date DATETIME NULL, status ENUM('Draft','Published','Archived') NOT NULL DEFAULT 'Draft', is_pinned TINYINT(1) NOT NULL DEFAULT 0, attachment_path VARCHAR(255) NULL, created_by BIGINT UNSIGNED NULL, updated_by BIGINT UNSIGNED NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (id), KEY idx_announcements_status (status, publish_date), KEY idx_announcements_priority (priority), KEY idx_announcements_expiry (expiry_date)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->migrateLegacyAnnouncementColumns();
        $this->database()->execute("CREATE TABLE IF NOT EXISTS announcement_roles (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, announcement_id BIGINT UNSIGNED NOT NULL, role_id BIGINT UNSIGNED NOT NULL, PRIMARY KEY (id), UNIQUE KEY uq_announcement_role (announcement_id, role_id), KEY idx_announcement_roles_role (role_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->database()->execute("CREATE TABLE IF NOT EXISTS announcement_reads (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, announcement_id BIGINT UNSIGNED NOT NULL, employee_id BIGINT UNSIGNED NOT NULL, read_at DATETIME NOT NULL, PRIMARY KEY (id), UNIQUE KEY uq_announcement_read (announcement_id, employee_id), KEY idx_announcement_reads_employee (employee_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    private function migrateLegacyAnnouncementColumns(): void
    {
        if ($this->hasColumn('announcements', 'content') && !$this->hasColumn('announcements', 'message')) {
            $this->database()->execute('ALTER TABLE announcements CHANGE content message TEXT NOT NULL');
        }
        if ($this->hasColumn('announcements', 'publish_at') && !$this->hasColumn('announcements', 'publish_date')) {
            $this->database()->execute('ALTER TABLE announcements CHANGE publish_at publish_date DATETIME NOT NULL');
        }
        if ($this->hasColumn('announcements', 'expires_at') && !$this->hasColumn('announcements', 'expiry_date')) {
            $this->database()->execute('ALTER TABLE announcements CHANGE expires_at expiry_date DATETIME NULL');
        }
        if (!$this->hasColumn('announcements', 'visibility')) {
            $this->database()->execute("ALTER TABLE announcements ADD visibility ENUM('All','Selected Roles') NOT NULL DEFAULT 'All' AFTER priority");
        }
        if (!$this->hasColumn('announcements', 'attachment_path')) {
            $this->database()->execute('ALTER TABLE announcements ADD attachment_path VARCHAR(255) NULL AFTER is_pinned');
        }

        $this->database()->execute("UPDATE announcements SET priority = CASE LOWER(priority) WHEN 'low' THEN 'Low' WHEN 'medium' THEN 'Normal' WHEN 'normal' THEN 'Normal' WHEN 'high' THEN 'High' WHEN 'urgent' THEN 'Urgent' ELSE 'Normal' END");
        $this->database()->execute("UPDATE announcements SET status = CASE LOWER(status) WHEN 'draft' THEN 'Draft' WHEN 'archived' THEN 'Archived' ELSE 'Published' END");
        $this->database()->execute("ALTER TABLE announcements MODIFY title VARCHAR(255) NOT NULL, MODIFY priority ENUM('Low','Normal','High','Urgent') NOT NULL DEFAULT 'Normal', MODIFY status ENUM('Draft','Published','Archived') NOT NULL DEFAULT 'Draft'");
    }

    private function hasColumn(string $table, string $column): bool
    {
        return $this->database()->value(
            'SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column LIMIT 1',
            ['table' => $table, 'column' => $column]
        ) !== null;
    }

    private function ensureRoles(): void
    {
        foreach (self::ROLES as $role) {
            $slug = strtolower(str_replace(' ', '-', $role));
            if ($this->database()->value('SELECT id FROM roles WHERE slug = :slug LIMIT 1', ['slug' => $slug]) === null) {
                $this->insert('roles', ['name' => $role, 'slug' => $slug]);
            }
        }
    }

    private function baseSql(): string
    {
        return "SELECT a.*, COALESCE(u.username, 'System') AS creator_name, GROUP_CONCAT(r.name ORDER BY r.name SEPARATOR ', ') AS role_names FROM announcements a LEFT JOIN users u ON u.id = a.created_by LEFT JOIN announcement_roles ar ON ar.announcement_id = a.id LEFT JOIN roles r ON r.id = ar.role_id GROUP BY a.id";
    }

    private function mapRow(array $row): array
    {
        $status = (string) $row['status'];
        $now = new DateTimeImmutable();
        $publish = new DateTimeImmutable((string) $row['publish_date']);
        $expiry = !empty($row['expiry_date']) ? new DateTimeImmutable((string) $row['expiry_date']) : null;
        if ($status === 'Published' && $publish > $now) {
            $status = 'Scheduled';
        } elseif ($status === 'Published' && $expiry !== null && $expiry < $now) {
            $status = 'Expired';
        }
        $roles = (string) ($row['role_names'] ?? '');
        $audience = (string) $row['visibility'] === 'All' ? 'Everyone' : ($roles !== '' ? $roles : 'Selected Roles');

        return [
            'db_id' => (int) $row['id'],
            'id' => (string) $row['id'],
            'title' => (string) $row['title'],
            'message' => (string) $row['message'],
            'category' => (string) ($row['category'] ?? 'General Notice'),
            'audience' => $audience,
            'selected_roles' => $roles === '' ? [] : array_map('trim', explode(',', $roles)),
            'priority' => (string) $row['priority'],
            'status' => $status,
            'raw_status' => (string) $row['status'],
            'publish_date' => substr((string) $row['publish_date'], 0, 10),
            'publish_time' => substr((string) $row['publish_date'], 11, 5),
            'expiry_date' => $row['expiry_date'] ? substr((string) $row['expiry_date'], 0, 10) : '',
            'created_by' => (string) $row['creator_name'],
            'pinned' => (bool) $row['is_pinned'],
            'attachment' => $row['attachment_path'] ? basename((string) $row['attachment_path']) : '',
        ];
    }

    private function findForView(?int $id): ?array
    {
        if ($id === null || $id <= 0) {
            return null;
        }

        $row = $this->queryOne('SELECT * FROM (' . $this->baseSql() . ') x WHERE x.id = :id LIMIT 1', ['id' => $id]);
        return $row ? $this->mapRow($row) : null;
    }

    private function findRaw(int $id): ?array
    {
        return $this->queryOne('SELECT * FROM announcements WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    private function summaryCards(): array
    {
        $now = date('Y-m-d H:i:s');
        return [
            ['label' => 'Total Announcements', 'value' => (int) $this->database()->value('SELECT COUNT(*) FROM announcements'), 'icon' => 'fa-solid fa-bullhorn', 'tone' => 'primary'],
            ['label' => 'Published', 'value' => (int) $this->database()->value("SELECT COUNT(*) FROM announcements WHERE status = 'Published' AND publish_date <= :now_start AND (expiry_date IS NULL OR expiry_date >= :now_end)", ['now_start' => $now, 'now_end' => $now]), 'icon' => 'fa-solid fa-circle-check', 'tone' => 'success'],
            ['label' => 'Drafts', 'value' => (int) $this->database()->value("SELECT COUNT(*) FROM announcements WHERE status = 'Draft'"), 'icon' => 'fa-solid fa-pen', 'tone' => 'warning'],
            ['label' => 'Archived', 'value' => (int) $this->database()->value("SELECT COUNT(*) FROM announcements WHERE status = 'Archived'"), 'icon' => 'fa-solid fa-box-archive', 'tone' => 'muted'],
            ['label' => 'Expired', 'value' => (int) $this->database()->value("SELECT COUNT(*) FROM announcements WHERE status = 'Published' AND expiry_date IS NOT NULL AND expiry_date < :now", ['now' => $now]), 'icon' => 'fa-solid fa-hourglass-end', 'tone' => 'danger'],
            ['label' => 'High Priority', 'value' => (int) $this->database()->value("SELECT COUNT(*) FROM announcements WHERE priority IN ('High','Urgent')"), 'icon' => 'fa-solid fa-triangle-exclamation', 'tone' => 'orange'],
            ['label' => 'Published Today', 'value' => (int) $this->database()->value("SELECT COUNT(*) FROM announcements WHERE status = 'Published' AND DATE(publish_date) = CURDATE()"), 'icon' => 'fa-solid fa-calendar-day', 'tone' => 'info'],
        ];
    }

    private function readStats(?int $announcementId): array
    {
        if ($announcementId === null || $announcementId <= 0) {
            return ['views' => 0, 'acknowledged' => 0, 'unread' => 0, 'comments' => 0];
        }
        $views = (int) $this->database()->value('SELECT COUNT(*) FROM announcement_reads WHERE announcement_id = :id', ['id' => $announcementId]);
        return ['views' => $views, 'acknowledged' => $views, 'unread' => 0, 'comments' => 0];
    }

    private function syncRoles(Database $database, int $announcementId, array $roles): void
    {
        $database->delete('announcement_roles', ['announcement_id' => $announcementId]);
        foreach ($roles as $roleName) {
            $roleId = $this->roleId($roleName);
            if ($roleId !== null) {
                $database->insert('announcement_roles', ['announcement_id' => $announcementId, 'role_id' => $roleId]);
            }
        }
    }

    private function roleId(string $roleName): ?int
    {
        $slug = strtolower(str_replace(' ', '-', trim($roleName)));
        $id = $this->database()->value('SELECT id FROM roles WHERE slug = :slug LIMIT 1', ['slug' => $slug]);
        return $id === null ? null : (int) $id;
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = strtolower(trim((string) preg_replace('/[^A-Za-z0-9]+/', '-', $title), '-'));
        $base = $base !== '' ? $base : 'announcement';
        $slug = $base;
        $counter = 2;

        while ($this->slugExists($slug, $ignoreId)) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT id FROM announcements WHERE slug = :slug';
        $bindings = ['slug' => $slug];

        if ($ignoreId !== null && $ignoreId > 0) {
            $sql .= ' AND id != :id';
            $bindings['id'] = $ignoreId;
        }

        return $this->database()->value($sql . ' LIMIT 1', $bindings) !== null;
    }
    private function guardDuplicate(string $title, string $publishDate, ?int $ignoreId = null): void
    {
        $sql = "SELECT id FROM announcements WHERE title = :title AND publish_date = :publish_date AND status != 'Archived'";
        $bindings = ['title' => $title, 'publish_date' => $publishDate];
        if ($ignoreId !== null) {
            $sql .= ' AND id != :id';
            $bindings['id'] = $ignoreId;
        }
        if ($this->database()->value($sql . ' LIMIT 1', $bindings) !== null) {
            throw new RuntimeException('An active announcement with this title and publish date already exists.');
        }
    }

    private function dateTime(string $date, string $time, string $label): DateTimeImmutable
    {
        $date = trim($date);
        $time = trim($time) !== '' ? trim($time) : '00:00';
        if ($date === '') {
            throw new RuntimeException("{$label} is required.");
        }
        $formats = ['Y-m-d H:i', 'd-m-Y H:i', 'm/d/Y H:i', 'Y-m-d H:i:s'];
        foreach ($formats as $format) {
            $value = DateTimeImmutable::createFromFormat($format, $date . ' ' . $time);
            if ($value instanceof DateTimeImmutable) {
                return $value;
            }
        }
        throw new RuntimeException("{$label} is invalid.");
    }

    private function emptyAnnouncement(): array
    {
        return ['db_id' => 0, 'id' => '', 'title' => 'Announcement not found', 'message' => '', 'category' => 'General Notice', 'audience' => 'Everyone', 'selected_roles' => [], 'priority' => 'Normal', 'status' => 'Draft', 'raw_status' => 'Draft', 'publish_date' => date('Y-m-d'), 'publish_time' => date('H:i'), 'expiry_date' => '', 'created_by' => 'System', 'pinned' => false, 'attachment' => ''];
    }

    private function currentUserId(): ?int
    {
        $id = Session::get('auth.user_id');
        return $id !== null && (int) $id > 0 ? (int) $id : null;
    }

    private function log(string $activity, int $id, mixed $old, mixed $new): void
    {
        try {
            $this->database()->insert('activity_logs', ['log_code' => 'ACT-' . date('YmdHis') . '-' . random_int(100, 999), 'user_id' => $this->currentUserId(), 'activity_type' => 'Announcement', 'module' => 'Announcements Management', 'activity' => $activity, 'entity_type' => 'announcement', 'entity_id' => $id, 'old_value' => $old === null ? null : json_encode($old), 'new_value' => json_encode($new), 'status' => 'Success']);
        } catch (Throwable) {
        }
    }
}














