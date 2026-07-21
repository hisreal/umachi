<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Session;
use App\Services\FuelPaymentReconciler;
use RuntimeException;
use Throwable;

class FuelSale extends BaseModel
{
    private const STATUS_LABELS = [
        'pending' => 'Pending',
        'verified' => 'Verified',
        'rejected' => 'Rejected',
        'correction_requested' => 'Correction Requested',
        'cancelled' => 'Cancelled',
    ];

    public function boot(): void
    {
        $this->ensureSchema();
    }

    public function recordClockOutSale(Database $database, array $employee, array $duty, array $attendance, array $data): int
    {
        $opening = $this->number($data['opening_meter'] ?? null, 'Opening meter reading');
        $closing = $this->number($data['closing_meter'] ?? null, 'Closing meter reading');
        if ($closing < $opening) {
            throw new RuntimeException('Closing meter reading cannot be less than the opening meter reading.');
        }


        $pumpId = (int) ($duty['pump_id'] ?? 0);
        $shiftId = (int) ($duty['shift_id'] ?? 0);
        $employeeId = (int) ($employee['db_id'] ?? 0);
        $saleDate = date('Y-m-d');

        if ($pumpId <= 0 || $shiftId <= 0 || $employeeId <= 0) {
            throw new RuntimeException('A valid duty assignment is required before fuel sales can be submitted.');
        }

        $duplicate = $database->value(
            "SELECT id FROM fuel_sales WHERE pump_id = :pump_id AND shift_id = :shift_id AND sale_date = :sale_date AND deleted_at IS NULL LIMIT 1",
            ['pump_id' => $pumpId, 'shift_id' => $shiftId, 'sale_date' => $saleDate]
        );
        if ($duplicate !== null) {
            throw new RuntimeException('Fuel sales have already been submitted for this pump, shift, and date.');
        }

        $fuelTypeId = (int) ($duty['fuel_type_id'] ?? $this->fuelTypeId((string) ($duty['fuel_type'] ?? '')));
        $unitPrice = $this->currentPrice($fuelTypeId);
        $litresSold = round($closing - $opening, 3);
        if ($litresSold < 0) {
            throw new RuntimeException('Litres sold cannot be negative.');
        }
        $expectedAmount = round($litresSold * $unitPrice, 2);
        $payment = FuelPaymentReconciler::reconcile(
            $expectedAmount,
            $this->money($data['cash_received'] ?? null, 'Cash received'),
            $this->money($data['pos_received'] ?? null, 'POS / Card payments'),
            $this->money($data['bank_transfer_received'] ?? null, 'Bank transfer'),
            (string) ($data['payment_remark'] ?? '')
        );
        $cashReceived = $payment['cash_received'];
        $posReceived = $payment['pos_received'];
        $bankTransferReceived = $payment['bank_transfer_received'];
        $totalReceived = $payment['total_received'];
        $differenceAmount = $payment['difference_amount'];
        $balanceStatus = $payment['balance_status'];
        $paymentRemark = $payment['payment_remark'];
        $amountCollected = $totalReceived;
        $variance = round($totalReceived - $expectedAmount, 2);
        $remarks = trim((string) ($data['remarks'] ?? ''));
        $saleCode = $this->saleCode();

        $saleId = (int) $database->insert('fuel_sales', [
            'sale_code' => $saleCode,
            'sale_date' => $saleDate,
            'employee_id' => $employeeId,
            'attendance_id' => (int) $attendance['id'],
            'duty_assignment_id' => (int) ($duty['duty_assignment_id'] ?? 0),
            'roster_assignment_id' => $this->validRosterAssignmentId($database, (int) ($duty['legacy_roster_assignment_id'] ?? 0)),
            'pump_id' => $pumpId,
            'shift_id' => $shiftId,
            'fuel_type_id' => $fuelTypeId,
            'fuel_type' => $this->fuelName((string) ($duty['fuel_type'] ?? '')),
            'opening_meter' => $opening,
            'closing_meter' => $closing,
            'litres_sold' => $litresSold,
            'liters_sold' => $litresSold,
            'unit_price' => $unitPrice,
            'price_per_litre' => $unitPrice,
            'expected_amount' => $expectedAmount,
            'total_amount' => $totalReceived,
            'amount_collected' => $amountCollected,
            'variance' => $variance,
            'cash_received' => $cashReceived,
            'pos_received' => $posReceived,
            'bank_transfer_received' => $bankTransferReceived,
            'total_received' => $totalReceived,
            'difference_amount' => $differenceAmount,
            'balance_status' => $balanceStatus,
            'payment_remark' => $paymentRemark !== '' ? $paymentRemark : null,
            'remarks' => $remarks !== '' ? $remarks : null,
            'submitted_at' => date('Y-m-d H:i:s'),
            'status' => 'pending',
            'verification_status' => 'Pending',
        ]);

        $this->recordMeterReading($database, $pumpId, $employeeId, $opening, 'opening', $remarks);
        $this->recordMeterReading($database, $pumpId, $employeeId, $closing, 'closing', $remarks);
        $database->update('pumps', ['current_meter_reading' => $closing], ['id' => $pumpId]);
        $database->update('duty_assignments', ['status' => 'Completed'], ['id' => (int) ($duty['duty_assignment_id'] ?? 0)]);

        $this->logActivity('Fuel Sale Submitted', $saleId, null, [
            'sale_code' => $saleCode,
            'litres_sold' => $litresSold,
            'amount_collected' => $amountCollected,
        ]);
        $this->logActivity('Fuel Payment Summary Submitted', $saleId, null, [
            'expected_amount' => $expectedAmount,
            'total_received' => $totalReceived,
            'balance_status' => $balanceStatus,
        ]);
        if ($balanceStatus !== 'balanced') {
            $this->logActivity('Payment Difference Recorded', $saleId, null, [
                'difference_amount' => $differenceAmount,
                'balance_status' => $balanceStatus,
                'payment_remark' => $paymentRemark,
            ]);
        }

        return $saleId;
    }

    public function dashboardData(array $filters = []): array
    {
        $this->boot();
        $sales = $this->sales($filters);

        return [
            'fuelSales' => $sales,
            'fuelSalesSummary' => $this->summaryCards(),
            'fuelTypes' => $this->distinctList('fuel_type'),
            'pumps' => $this->distinctList('pump'),
            'shifts' => $this->distinctList('shift'),
            'salesStatuses' => ['Pending', 'Verified', 'Rejected', 'Correction Requested'],
            'attendants' => $this->distinctList('attendant'),
            'salesStatusClasses' => [
                'Pending' => 'fuel-status--pending',
                'Verified' => 'fuel-status--verified',
                'Rejected' => 'fuel-status--rejected',
                'Correction Requested' => 'fuel-status--pending',
                'Cancelled' => 'fuel-status--rejected',
            ],
            'selectedSale' => $this->selectedSale($sales),
            'pumpMeterReadings' => $this->pumpMeterReadings(),
            'fuelReportKpis' => $this->reportKpis(),
            'fuelChartData' => $this->chartData(),
            'pumpMeterSummary' => $this->pumpMeterSummary(),
        ];
    }

    public function verify(string $saleCode, string $action, string $notes = ''): void
    {
        $this->boot();
        $sale = $this->findByCode($saleCode);
        if ($sale === null) {
            throw new RuntimeException('Fuel sale record not found.');
        }

        $status = match ($action) {
            'verify' => 'verified',
            'reject' => 'rejected',
            'correction' => 'correction_requested',
            default => throw new RuntimeException('Select a valid verification action.'),
        };

        $this->transaction(function (Database $database) use ($sale, $status, $notes): void {
            $database->update('fuel_sales', [
                'status' => $status,
                'verification_status' => self::STATUS_LABELS[$status],
                'verified_by' => $this->currentUserId(),
                'verified_at' => date('Y-m-d H:i:s'),
                'rejection_reason' => $status === 'verified' ? null : ($notes !== '' ? $notes : self::STATUS_LABELS[$status]),
            ], ['id' => (int) $sale['id']]);

            if ($status === 'verified') {
                $this->deductInventory($database, $sale);
            }

            $this->logActivity('Fuel Sale ' . self::STATUS_LABELS[$status], (int) $sale['id'], ['status' => $sale['status']], ['status' => $status, 'notes' => $notes]);
        });
    }

    public function sales(array $filters = []): array
    {
        $this->boot();
        $rows = $this->query(
            "SELECT fs.*, CONCAT(e.first_name, ' ', e.last_name) AS attendant_name, e.employee_code,
                    p.pump_code, p.pump_name, ft.name AS fuel_name, ft.short_name AS fuel_short_name, s.name AS shift_name
             FROM fuel_sales fs
             INNER JOIN employees e ON e.id = fs.employee_id
             INNER JOIN pumps p ON p.id = fs.pump_id
             INNER JOIN fuel_types ft ON ft.id = fs.fuel_type_id
             LEFT JOIN shifts s ON s.id = fs.shift_id
             WHERE fs.deleted_at IS NULL
             ORDER BY fs.sale_date DESC, fs.id DESC
             LIMIT 500"
        );

        return array_map([$this, 'mapSale'], $rows);
    }

    private function selectedSale(array $sales): array
    {
        $requested = (string) ($_GET['transaction'] ?? '');
        foreach ($sales as $sale) {
            if ($sale['transaction_id'] === $requested) {
                return $sale;
            }
        }

        return $sales[0] ?? [
            'transaction_id' => 'N/A', 'date' => date('Y-m-d'), 'pump' => 'N/A', 'fuel_type' => 'N/A', 'attendant' => 'N/A',
            'shift' => 'N/A', 'opening_meter' => 0, 'closing_meter' => 0, 'liters_sold' => 0, 'amount' => 0,
            'amount_collected' => 0, 'expected_amount' => 0, 'variance' => 0, 'status' => 'Pending', 'submitted_time' => '-',
            'cash_received' => 0, 'pos_received' => 0, 'bank_transfer_received' => 0, 'total_received' => 0, 'difference_amount' => 0, 'balance_status' => 'balanced', 'payment_remark' => '',
        ];
    }

    private function mapSale(array $row): array
    {
        $status = self::STATUS_LABELS[(string) ($row['status'] ?? 'pending')] ?? (string) ($row['verification_status'] ?? 'Pending');
        $pump = trim((string) $row['pump_code'] . ' - ' . (string) $row['pump_name'], ' -');
        $fuel = trim((string) $row['fuel_name'] . ' (' . (string) $row['fuel_short_name'] . ')');

        return [
            'id' => (int) $row['id'],
            'transaction_id' => (string) $row['sale_code'],
            'date' => (string) $row['sale_date'],
            'pump' => $pump,
            'fuel_type' => $fuel,
            'attendant' => (string) $row['attendant_name'],
            'employee_id' => (string) $row['employee_code'],
            'shift' => (string) ($row['shift_name'] ?? 'Unassigned'),
            'opening_meter' => (float) $row['opening_meter'],
            'closing_meter' => (float) $row['closing_meter'],
            'liters_sold' => (float) ($row['litres_sold'] ?? $row['liters_sold'] ?? 0),
            'litres_sold' => (float) ($row['litres_sold'] ?? $row['liters_sold'] ?? 0),
            'unit_price' => (float) ($row['unit_price'] ?? $row['price_per_litre'] ?? 0),
            'expected_amount' => (float) ($row['expected_amount'] ?? $row['total_amount'] ?? 0),
            'amount' => (float) ($row['amount_collected'] ?? $row['total_amount'] ?? 0),
            'amount_collected' => (float) ($row['amount_collected'] ?? $row['total_amount'] ?? 0),
            'variance' => (float) ($row['variance'] ?? 0),
            'cash_received' => (float) ($row['cash_received'] ?? 0),
            'pos_received' => (float) ($row['pos_received'] ?? 0),
            'bank_transfer_received' => (float) ($row['bank_transfer_received'] ?? 0),
            'total_received' => (float) ($row['total_received'] ?? $row['amount_collected'] ?? 0),
            'difference_amount' => (float) ($row['difference_amount'] ?? 0),
            'balance_status' => (string) ($row['balance_status'] ?? 'balanced'),
            'payment_remark' => (string) ($row['payment_remark'] ?? ''),
            'status' => $status,
            'status_key' => (string) ($row['status'] ?? 'pending'),
            'submitted_time' => empty($row['submitted_at']) ? '-' : date('h:i A', strtotime((string) $row['submitted_at'])),
            'remarks' => (string) ($row['remarks'] ?? $row['rejection_reason'] ?? ''),
        ];
    }

    private function summaryCards(): array
    {
        $today = date('Y-m-d');
        $todayRow = $this->queryOne("SELECT COALESCE(SUM(amount_collected), 0) AS revenue, COALESCE(SUM(litres_sold), 0) AS litres, COUNT(*) AS total FROM fuel_sales WHERE deleted_at IS NULL AND sale_date = :today", ['today' => $today]) ?? [];
        $pending = (int) $this->database()->value("SELECT COUNT(*) FROM fuel_sales WHERE deleted_at IS NULL AND status = 'pending'");
        $verified = (int) $this->database()->value("SELECT COUNT(*) FROM fuel_sales WHERE deleted_at IS NULL AND status = 'verified'");
        $rejected = (int) $this->database()->value("SELECT COUNT(*) FROM fuel_sales WHERE deleted_at IS NULL AND status = 'rejected'");
        $activePumps = (int) $this->database()->value("SELECT COUNT(*) FROM pumps WHERE deleted_at IS NULL AND status = 'active'");
        $totalPumps = (int) $this->database()->value("SELECT COUNT(*) FROM pumps WHERE deleted_at IS NULL");
        $fuelLitres = $this->todayFuelLitres();

        return [
            ['label' => "Today's Total Sales", 'value' => 'NGN ' . number_format((float) ($todayRow['revenue'] ?? 0), 2), 'icon' => 'fa-solid fa-money-bill-trend-up', 'tone' => 'primary'],
            ['label' => "Today's Litres Sold", 'value' => number_format((float) ($todayRow['litres'] ?? 0), 2) . ' L', 'icon' => 'fa-solid fa-gas-pump', 'tone' => 'success'],
            ['label' => "Today's Transactions", 'value' => (string) ((int) ($todayRow['total'] ?? 0)), 'icon' => 'fa-solid fa-receipt', 'tone' => 'info'],
            ['label' => 'Pending Verification', 'value' => $pending . ' Pending', 'icon' => 'fa-solid fa-hourglass-half', 'tone' => 'warning'],
            ['label' => 'Verified Sales', 'value' => $verified . ' Verified', 'icon' => 'fa-solid fa-circle-check', 'tone' => 'success'],
            ['label' => 'Rejected Sales', 'value' => $rejected . ' Rejected', 'icon' => 'fa-solid fa-circle-xmark', 'tone' => 'danger'],
            ['label' => 'Petrol Sales', 'value' => number_format($fuelLitres['Petrol'] ?? 0, 2) . ' L', 'icon' => 'fa-solid fa-gas-pump', 'tone' => 'primary'],
            ['label' => 'Diesel Sales', 'value' => number_format($fuelLitres['Diesel'] ?? 0, 2) . ' L', 'icon' => 'fa-solid fa-oil-can', 'tone' => 'warning'],
            ['label' => 'Gas Sales', 'value' => number_format($fuelLitres['Gas'] ?? 0, 2) . ' L', 'icon' => 'fa-solid fa-fire-flame-simple', 'tone' => 'info'],
            ['label' => 'Active Pumps', 'value' => $activePumps . ' / ' . $totalPumps . ' Active', 'icon' => 'fa-solid fa-oil-can', 'tone' => 'secondary'],
        ];
    }

    private function todayFuelLitres(): array
    {
        $totals = ['Petrol' => 0.0, 'Diesel' => 0.0, 'Gas' => 0.0];
        foreach ($this->query("SELECT ft.name, COALESCE(SUM(fs.litres_sold), 0) AS litres FROM fuel_sales fs INNER JOIN fuel_types ft ON ft.id = fs.fuel_type_id WHERE fs.deleted_at IS NULL AND fs.sale_date = :today GROUP BY ft.name", ['today' => date('Y-m-d')]) as $row) {
            $totals[(string) $row['name']] = (float) $row['litres'];
        }

        return $totals;
    }
    private function pumpMeterReadings(): array
    {
        return array_map(static function (array $sale): array {
            return [
                'date' => $sale['date'],
                'pump' => $sale['pump'],
                'fuel_type' => $sale['fuel_type'],
                'opening_meter' => $sale['opening_meter'],
                'closing_meter' => $sale['closing_meter'],
                'difference' => $sale['closing_meter'] - $sale['opening_meter'],
                'shift' => $sale['shift'],
                'attendant' => $sale['attendant'],
                'status' => $sale['status'],
            ];
        }, $this->sales());
    }

    private function reportKpis(): array
    {
        $row = $this->queryOne("SELECT COALESCE(SUM(amount_collected), 0) AS revenue, COALESCE(SUM(litres_sold), 0) AS litres, COALESCE(AVG(amount_collected), 0) AS average_sale FROM fuel_sales WHERE deleted_at IS NULL") ?? [];
        $bestPump = (string) ($this->database()->value("SELECT p.pump_code FROM fuel_sales fs INNER JOIN pumps p ON p.id = fs.pump_id WHERE fs.deleted_at IS NULL GROUP BY fs.pump_id ORDER BY SUM(fs.litres_sold) DESC LIMIT 1") ?? 'N/A');
        $bestFuel = (string) ($this->database()->value("SELECT ft.name FROM fuel_sales fs INNER JOIN fuel_types ft ON ft.id = fs.fuel_type_id WHERE fs.deleted_at IS NULL GROUP BY fs.fuel_type_id ORDER BY SUM(fs.litres_sold) DESC LIMIT 1") ?? 'N/A');

        return [
            ['label' => 'Total Revenue', 'value' => 'NGN ' . number_format((float) ($row['revenue'] ?? 0), 2), 'icon' => 'fa-solid fa-naira-sign', 'tone' => 'primary'],
            ['label' => 'Total Liters Sold', 'value' => number_format((float) ($row['litres'] ?? 0), 2) . ' L', 'icon' => 'fa-solid fa-gas-pump', 'tone' => 'success'],
            ['label' => 'Average Sale', 'value' => 'NGN ' . number_format((float) ($row['average_sale'] ?? 0), 2), 'icon' => 'fa-solid fa-chart-line', 'tone' => 'info'],
            ['label' => 'Best Performing Pump', 'value' => $bestPump, 'icon' => 'fa-solid fa-trophy', 'tone' => 'warning'],
            ['label' => 'Best Selling Fuel Type', 'value' => $bestFuel, 'icon' => 'fa-solid fa-fire-flame-curved', 'tone' => 'secondary'],
        ];
    }

    private function chartData(): array
    {
        $dailyRows = $this->query("SELECT sale_date, COALESCE(SUM(amount_collected), 0) AS total FROM fuel_sales WHERE deleted_at IS NULL AND sale_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY sale_date ORDER BY sale_date");
        $fuelRows = $this->query("SELECT ft.name, COALESCE(SUM(fs.litres_sold), 0) AS litres FROM fuel_sales fs INNER JOIN fuel_types ft ON ft.id = fs.fuel_type_id WHERE fs.deleted_at IS NULL GROUP BY ft.id ORDER BY ft.name");
        $pumpRows = $this->query("SELECT p.pump_code, COALESCE(SUM(fs.litres_sold), 0) AS litres FROM fuel_sales fs INNER JOIN pumps p ON p.id = fs.pump_id WHERE fs.deleted_at IS NULL GROUP BY p.id ORDER BY litres DESC LIMIT 10");

        return [
            'daily' => ['labels' => array_column($dailyRows, 'sale_date'), 'values' => array_map('floatval', array_column($dailyRows, 'total'))],
            'weekly' => ['labels' => array_column($dailyRows, 'sale_date'), 'values' => array_map('floatval', array_column($dailyRows, 'total'))],
            'monthly' => ['labels' => array_column($dailyRows, 'sale_date'), 'values' => array_map('floatval', array_column($dailyRows, 'total'))],
            'fuelDistribution' => ['labels' => array_column($fuelRows, 'name'), 'values' => array_map('floatval', array_column($fuelRows, 'litres'))],
            'pumpPerformance' => ['labels' => array_column($pumpRows, 'pump_code'), 'values' => array_map('floatval', array_column($pumpRows, 'litres'))],
        ];
    }

    private function pumpMeterSummary(): array
    {
        $today = date('Y-m-d');
        $totalPumps = (int) $this->database()->value("SELECT COUNT(*) FROM pumps WHERE deleted_at IS NULL");
        $litres = (float) $this->database()->value("SELECT COALESCE(SUM(litres_sold), 0) FROM fuel_sales WHERE deleted_at IS NULL AND sale_date = :today", ['today' => $today]);
        $highest = (string) ($this->database()->value("SELECT p.pump_code FROM fuel_sales fs INNER JOIN pumps p ON p.id = fs.pump_id WHERE fs.deleted_at IS NULL GROUP BY fs.pump_id ORDER BY SUM(fs.litres_sold) DESC LIMIT 1") ?? 'N/A');
        $lowest = (string) ($this->database()->value("SELECT p.pump_code FROM fuel_sales fs INNER JOIN pumps p ON p.id = fs.pump_id WHERE fs.deleted_at IS NULL GROUP BY fs.pump_id ORDER BY SUM(fs.litres_sold) ASC LIMIT 1") ?? 'N/A');

        return [
            ['label' => 'Total Pumps', 'value' => $totalPumps . ' Pumps', 'icon' => 'fa-solid fa-gas-pump', 'tone' => 'primary'],
            ['label' => 'Total Liters Dispensed Today', 'value' => number_format($litres, 2) . ' L', 'icon' => 'fa-solid fa-droplet', 'tone' => 'success'],
            ['label' => 'Highest Dispensing Pump', 'value' => $highest, 'icon' => 'fa-solid fa-arrow-trend-up', 'tone' => 'warning'],
            ['label' => 'Lowest Dispensing Pump', 'value' => $lowest, 'icon' => 'fa-solid fa-arrow-trend-down', 'tone' => 'danger'],
        ];
    }

    private function distinctList(string $key): array
    {
        return array_values(array_unique(array_filter(array_column($this->sales(), $key))));
    }

    private function findByCode(string $saleCode): ?array
    {
        return $this->queryOne('SELECT * FROM fuel_sales WHERE sale_code = :sale_code AND deleted_at IS NULL LIMIT 1', ['sale_code' => $saleCode]);
    }

    private function deductInventory(Database $database, array $sale): void
    {
        $movementExists = $database->value("SELECT id FROM fuel_inventory_movements WHERE fuel_sale_id = :sale_id AND movement_type = 'sale' LIMIT 1", ['sale_id' => (int) $sale['id']]);
        if ($movementExists !== null) {
            return;
        }

        $level = $database->selectOne('SELECT id, current_stock_litres, minimum_stock_litres FROM fuel_inventory_levels WHERE fuel_type_id = :fuel_type_id ORDER BY tank_id IS NULL DESC, id ASC LIMIT 1', ['fuel_type_id' => (int) $sale['fuel_type_id']]);
        if ($level === null) {
            throw new RuntimeException('Fuel inventory level is not configured for this fuel type.');
        }

        $remaining = (float) $level['current_stock_litres'] - (float) $sale['litres_sold'];
        if ($remaining < 0) {
            throw new RuntimeException('Fuel inventory is not sufficient for this verified sale.');
        }
        $database->update('fuel_inventory_levels', [
            'current_stock_litres' => $remaining,
            'last_updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => (int) $level['id']]);

        $database->insert('fuel_inventory_movements', [
            'fuel_type_id' => (int) $sale['fuel_type_id'],
            'movement_type' => 'sale',
            'quantity_litres' => -abs((float) $sale['litres_sold']),
            'unit_cost' => (float) ($sale['unit_price'] ?? 0),
            'movement_datetime' => date('Y-m-d H:i:s'),
            'fuel_sale_id' => (int) $sale['id'],
            'reference' => (string) $sale['sale_code'],
            'remarks' => 'Automatic inventory deduction after verified fuel sale.',
            'created_by' => $this->currentUserId(),
        ]);

        (new FuelInventory())->recordVerifiedSaleTransaction($database, $sale, (float) $level['current_stock_litres'], $remaining);
    }

    private function currentPrice(int $fuelTypeId): float
    {
        $price = $this->database()->value("SELECT price_per_litre FROM fuel_prices WHERE fuel_type_id = :fuel_type_id AND status = 'active' ORDER BY effective_from DESC, id DESC LIMIT 1", ['fuel_type_id' => $fuelTypeId]);
        if ($price === null || (float) $price <= 0) {
            throw new RuntimeException('Current fuel price is not configured for the assigned fuel type.');
        }

        return (float) $price;
    }

    private function fuelTypeId(string $fuelType): int
    {
        $name = $this->fuelName($fuelType);
        $row = $this->queryOne('SELECT id FROM fuel_types WHERE name = :name OR short_name = :short_name LIMIT 1', ['name' => $name, 'short_name' => strtoupper($name)]);
        if ($row === null) {
            throw new RuntimeException('Assigned fuel type could not be resolved.');
        }

        return (int) $row['id'];
    }

    private function fuelName(string $value): string
    {
        $value = trim($value);
        return match (strtoupper($value)) {
            'PMS' => 'Petrol',
            'AGO' => 'Diesel',
            'LPG' => 'Gas',
            default => str_contains($value, '(') ? trim((string) strtok($value, '(')) : ($value !== '' ? $value : 'Petrol'),
        };
    }

    private function recordMeterReading(Database $database, int $pumpId, int $employeeId, float $reading, string $type, string $remarks): void
    {
        try {
            $database->insert('pump_meter_readings', [
                'pump_id' => $pumpId,
                'employee_id' => $employeeId,
                'reading_at' => date('Y-m-d H:i:s'),
                'meter_reading' => $reading,
                'reading_type' => $type,
                'remarks' => $remarks !== '' ? $remarks : null,
            ]);
        } catch (Throwable) {
            // Meter history should not block the main fuel-sale transaction on legacy schemas.
        }
    }

    private function number(mixed $value, string $label): float
    {
        if ($value === null || trim((string) $value) === '' || !is_numeric((string) $value)) {
            throw new RuntimeException($label . ' must be numeric.');
        }
        $number = (float) $value;
        if ($number < 0) {
            throw new RuntimeException($label . ' cannot be negative.');
        }
        return $number;
    }

    private function money(mixed $value, string $label): float
    {
        if (is_numeric((string) $value) && (float) $value < 0) {
            throw new RuntimeException($label . ' cannot be negative.');
        }
        $normalized = preg_replace('/[^0-9.]/', '', (string) $value);
        return $this->number($normalized, $label);
    }

    private function saleCode(): string
    {
        do {
            $code = 'FS-' . date('Ymd') . '-' . random_int(1000, 9999);
        } while ($this->database()->value('SELECT id FROM fuel_sales WHERE sale_code = :code LIMIT 1', ['code' => $code]) !== null);

        return $code;
    }

    private function currentUserId(): ?int
    {
        $userId = Session::get('auth.user_id');
        return $userId === null ? null : (int) $userId;
    }

    private function logActivity(string $activity, int $saleId, mixed $oldValue, mixed $newValue): void
    {
        try {
            $this->insert('activity_logs', [
                'log_code' => 'ACT-' . date('YmdHis') . '-' . random_int(100, 999),
                'user_id' => $this->currentUserId(),
                'activity_type' => $activity,
                'module' => 'Fuel Sales',
                'activity' => $activity,
                'entity_type' => 'fuel_sale',
                'entity_id' => $saleId,
                'old_value' => $oldValue === null ? null : json_encode($oldValue, JSON_THROW_ON_ERROR),
                'new_value' => json_encode($newValue, JSON_THROW_ON_ERROR),
                'status' => 'success',
            ]);
        } catch (Throwable) {
        }
    }

    private function validRosterAssignmentId(Database $database, int $id): ?int
    {
        if ($id <= 0) {
            return null;
        }

        $exists = $database->value('SELECT id FROM roster_assignments WHERE id = :id LIMIT 1', ['id' => $id]);

        return $exists === null ? null : $id;
    }

    private function repairAttendanceForeignKey(): void
    {
        $schema = (string) $this->database()->value('SELECT DATABASE()');
        $foreignKey = $this->queryOne(
            "SELECT CONSTRAINT_NAME, REFERENCED_TABLE_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = 'fuel_sales' AND COLUMN_NAME = 'attendance_id' AND REFERENCED_TABLE_NAME IS NOT NULL LIMIT 1",
            ['schema' => $schema]
        );

        if ($foreignKey !== null && (string) $foreignKey['REFERENCED_TABLE_NAME'] !== 'attendance') {
            $constraint = preg_replace('/[^A-Za-z0-9_]/', '', (string) $foreignKey['CONSTRAINT_NAME']);
            if ($constraint !== '') {
                $this->database()->execute("ALTER TABLE fuel_sales DROP FOREIGN KEY `{$constraint}`");
            }
            $foreignKey = null;
        }

        $attendanceTableExists = $this->database()->value(
            "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = 'attendance'",
            ['schema' => $schema]
        );
        if ((int) $attendanceTableExists <= 0) {
            return;
        }

        $this->database()->execute('UPDATE fuel_sales fs LEFT JOIN attendance a ON a.id = fs.attendance_id SET fs.attendance_id = NULL WHERE fs.attendance_id IS NOT NULL AND a.id IS NULL');

        if ($foreignKey === null) {
            $this->database()->execute('ALTER TABLE fuel_sales ADD CONSTRAINT fk_fuel_sales_attendance FOREIGN KEY (attendance_id) REFERENCES attendance(id) ON DELETE SET NULL ON UPDATE CASCADE');
        }
    }
    private function ensureSchema(): void
    {
        $this->database()->execute("CREATE TABLE IF NOT EXISTS fuel_sales (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, sale_code VARCHAR(60) NOT NULL, sale_date DATE NOT NULL, employee_id BIGINT UNSIGNED NOT NULL, attendance_id BIGINT UNSIGNED NULL, duty_assignment_id BIGINT UNSIGNED NULL, roster_assignment_id BIGINT UNSIGNED NULL, pump_id BIGINT UNSIGNED NOT NULL, shift_id BIGINT UNSIGNED NULL, fuel_type_id BIGINT UNSIGNED NOT NULL, fuel_type ENUM('Petrol','Diesel','Gas') NOT NULL DEFAULT 'Petrol', opening_meter DECIMAL(14,3) NOT NULL, closing_meter DECIMAL(14,3) NOT NULL, litres_sold DECIMAL(14,3) NOT NULL, liters_sold DECIMAL(14,3) NOT NULL DEFAULT 0, unit_price DECIMAL(12,2) NOT NULL, price_per_litre DECIMAL(12,2) NOT NULL DEFAULT 0, expected_amount DECIMAL(14,2) NOT NULL DEFAULT 0, total_amount DECIMAL(14,2) NOT NULL, amount_collected DECIMAL(14,2) NOT NULL DEFAULT 0, variance DECIMAL(14,2) NOT NULL DEFAULT 0, submitted_at DATETIME NULL, verified_by BIGINT UNSIGNED NULL, verified_at DATETIME NULL, rejection_reason TEXT NULL, remarks TEXT NULL, status ENUM('pending','verified','rejected','correction_requested','cancelled') NOT NULL DEFAULT 'pending', verification_status VARCHAR(40) NOT NULL DEFAULT 'Pending', created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, deleted_at TIMESTAMP NULL DEFAULT NULL, PRIMARY KEY (id), UNIQUE KEY uq_fuel_sales_code (sale_code), KEY idx_fuel_sales_date_status (sale_date, status), KEY idx_fuel_sales_employee (employee_id), KEY idx_fuel_sales_pump (pump_id), KEY idx_fuel_sales_shift (shift_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $columns = array_column($this->query('SHOW COLUMNS FROM fuel_sales'), 'Field');
        $add = function (string $column, string $definition) use (&$columns): void {
            if (!in_array($column, $columns, true)) {
                $this->database()->execute("ALTER TABLE fuel_sales ADD COLUMN {$definition}");
                $columns[] = $column;
            }
        };

        $add('attendance_id', '`attendance_id` BIGINT UNSIGNED NULL AFTER `employee_id`');
        $add('duty_assignment_id', '`duty_assignment_id` BIGINT UNSIGNED NULL AFTER `attendance_id`');
        $add('roster_assignment_id', '`roster_assignment_id` BIGINT UNSIGNED NULL AFTER `duty_assignment_id`');
        $add('fuel_type', "`fuel_type` ENUM('Petrol','Diesel','Gas') NOT NULL DEFAULT 'Petrol' AFTER `fuel_type_id`");
        $add('liters_sold', '`liters_sold` DECIMAL(14,3) NOT NULL DEFAULT 0 AFTER `litres_sold`');
        $add('price_per_litre', '`price_per_litre` DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER `unit_price`');
        $add('expected_amount', '`expected_amount` DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER `price_per_litre`');
        $add('amount_collected', '`amount_collected` DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER `total_amount`');
        $add('variance', '`variance` DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER `amount_collected`');
        $add('cash_received', '`cash_received` DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER `variance`');
        $add('pos_received', '`pos_received` DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER `cash_received`');
        $add('bank_transfer_received', '`bank_transfer_received` DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER `pos_received`');
        $add('total_received', '`total_received` DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER `bank_transfer_received`');
        $add('difference_amount', '`difference_amount` DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER `total_received`');
        $add('balance_status', "`balance_status` VARCHAR(20) NOT NULL DEFAULT 'balanced' AFTER `difference_amount`");
        $add('payment_remark', '`payment_remark` TEXT NULL AFTER `balance_status`');
        $add('remarks', '`remarks` TEXT NULL AFTER `rejection_reason`');
        $add('verification_status', "`verification_status` VARCHAR(40) NOT NULL DEFAULT 'Pending' AFTER `status`");

        $this->database()->execute("ALTER TABLE fuel_sales MODIFY status ENUM('pending','verified','rejected','correction_requested','cancelled') NOT NULL DEFAULT 'pending'");
        $this->repairAttendanceForeignKey();
    }
}












