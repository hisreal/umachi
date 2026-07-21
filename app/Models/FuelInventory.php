<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Session;
use RuntimeException;
use Throwable;

class FuelInventory extends BaseModel
{
    private const FUEL_TYPES = [
        ['name' => 'Petrol', 'short_name' => 'PMS', 'tone' => 'success', 'icon' => 'fa-solid fa-gas-pump', 'reorder' => 5000.0],
        ['name' => 'Diesel', 'short_name' => 'AGO', 'tone' => 'warning', 'icon' => 'fa-solid fa-oil-can', 'reorder' => 3000.0],
        ['name' => 'Gas', 'short_name' => 'LPG', 'tone' => 'primary', 'icon' => 'fa-solid fa-fire-flame-simple', 'reorder' => 2500.0],
    ];

    public function boot(): void
    {
        $this->ensureSchema();
        $this->ensureFuelTypes();
        $this->ensureInventoryRows();
        $this->syncSimpleInventoryFromLevels();
    }

    public function dashboardData(): array
    {
        $this->boot();

        $fuelInventory = $this->currentInventory();
        $deliveryHistory = $this->deliveryHistory();
        $inventoryMovements = $this->movementSummary();
        $totalFuelInStock = array_sum(array_column($fuelInventory, 'remaining_stock'));
        $totalSoldToday = array_sum(array_column($fuelInventory, 'todays_sales'));
        $todaysDeliveries = array_sum(array_map(static fn (array $item): float => (float) $item['delivered_today'], $fuelInventory));
        $lowStockItems = array_values(array_filter($fuelInventory, static fn (array $item): bool => (float) $item['remaining_stock'] <= (float) $item['minimum_stock']));
        $lastDeliveryDates = array_filter(array_column($fuelInventory, 'last_delivery'));
        $lastDeliveryDate = $lastDeliveryDates === [] ? null : max($lastDeliveryDates);

        return [
            'fuelInventory' => $fuelInventory,
            'suppliers' => $this->suppliers(),
            'receivers' => $this->receivers(),
            'deliveryHistory' => $deliveryHistory,
            'inventoryMovements' => $inventoryMovements,
            'lowStockItems' => $lowStockItems,
            'summaryCards' => [
                ['label' => 'Total Fuel in Stock', 'value' => number_format($totalFuelInStock, 2) . ' L', 'icon' => 'fa-solid fa-warehouse', 'tone' => 'primary'],
                ['label' => 'Total Liters Sold Today', 'value' => number_format($totalSoldToday, 2) . ' L', 'icon' => 'fa-solid fa-arrow-trend-down', 'tone' => 'danger'],
                ['label' => 'Low Stock Alerts', 'value' => (string) count($lowStockItems), 'icon' => 'fa-solid fa-triangle-exclamation', 'tone' => 'warning'],
                ['label' => 'Today\'s Deliveries', 'value' => number_format($todaysDeliveries, 2) . ' L', 'icon' => 'fa-solid fa-truck-droplet', 'tone' => 'success'],
            ],
            'lastDeliveryDate' => $lastDeliveryDate,
            'chartData' => $this->chartData(),
        ];
    }

    public function saveDelivery(array $data): void
    {
        $this->boot();

        $fuelTypeId = (int) ($data['fuel_type_id'] ?? 0);
        $fuelType = $this->fuelTypeById($fuelTypeId);
        $deliveryId = (int)($data['delivery_id'] ?? 0);
        if ($deliveryId > 0) {
            $this->updateDelivery($deliveryId, $data);
            return;
        }

        if ($fuelType === null) {
            throw new RuntimeException('Select a valid fuel type.');
        }

        $deliveryDate = trim((string) ($data['delivery_date'] ?? ''));
        $deliveryTime = trim((string) ($data['delivery_time'] ?? '00:00'));
        $supplierName = trim((string) ($data['supplier_name'] ?? ''));
        $tankerNumber = trim((string) ($data['tanker_number'] ?? ''));
        $invoiceNumber = strtoupper(trim((string) ($data['invoice_number'] ?? '')));
        $remarks = trim((string) ($data['remarks'] ?? ''));
        $quantity = $this->positiveNumber($data['quantity_litres'] ?? null, 'Quantity delivered');
        $costPerLitre = $this->positiveNumber($data['cost_per_litre'] ?? null, 'Cost per litre');
        $receivedBy = (int) ($data['received_by'] ?? 0) ?: null;

        if ($deliveryDate === '' || strtotime($deliveryDate) === false) {
            throw new RuntimeException('Delivery date is required.');
        }
        if ($supplierName === '') {
            throw new RuntimeException('Supplier name is required.');
        }
        if ($tankerNumber === '') {
            throw new RuntimeException('Tanker number is required.');
        }
        if ($invoiceNumber === '') {
            throw new RuntimeException('Delivery reference number is required.');
        }

        $duplicate = $this->database()->value('SELECT id FROM fuel_deliveries WHERE invoice_number = :invoice AND deleted_at IS NULL LIMIT 1', ['invoice' => $invoiceNumber]);
        if ($duplicate !== null) {
            throw new RuntimeException('Delivery reference number already exists.');
        }

        $deliveryDateTime = date('Y-m-d H:i:s', strtotime($deliveryDate . ' ' . ($deliveryTime !== '' ? $deliveryTime : '00:00')));

        $this->transaction(function (Database $database) use ($fuelTypeId, $supplierName, $tankerNumber, $invoiceNumber, $remarks, $quantity, $costPerLitre, $receivedBy, $deliveryDateTime): void {
            $supplierId = $this->supplierId($database, $supplierName);
            $deliveryId = (int) $database->insert('fuel_deliveries', [
                'delivery_code' => $this->deliveryCode(),
                'supplier_id' => $supplierId,
                'delivery_datetime' => $deliveryDateTime,
                'tanker_number' => $tankerNumber,
                'invoice_number' => $invoiceNumber,
                'received_by' => $receivedBy,
                'remarks' => $remarks !== '' ? $remarks : null,
                'created_by' => $this->currentUserId(),
            ]);

            $itemId = (int) $database->insert('fuel_delivery_items', [
                'fuel_delivery_id' => $deliveryId,
                'fuel_type_id' => $fuelTypeId,
                'tank_id' => null,
                'quantity_litres' => $quantity,
                'cost_per_litre' => $costPerLitre,
            ]);

            $level = $this->inventoryLevel($database, $fuelTypeId);
            $previous = (float) $level['current_stock_litres'];
            $newBalance = $previous + $quantity;

            $database->update('fuel_inventory_levels', [
                'current_stock_litres' => $newBalance,
                'last_delivery_at' => $deliveryDateTime,
                'last_updated_at' => date('Y-m-d H:i:s'),
            ], ['id' => (int) $level['id']]);

            $this->updateSimpleInventory($database, $fuelTypeId, $newBalance, $level['minimum_stock_litres']);
            $this->recordSimpleTransaction($database, $fuelTypeId, 'Delivery', $quantity, $previous, $newBalance, $invoiceNumber, $remarks);

            $database->insert('fuel_inventory_movements', [
                'fuel_type_id' => $fuelTypeId,
                'tank_id' => null,
                'movement_type' => 'delivery',
                'quantity_litres' => $quantity,
                'unit_cost' => $costPerLitre,
                'movement_datetime' => $deliveryDateTime,
                'fuel_delivery_item_id' => $itemId,
                'fuel_sale_id' => null,
                'reference' => $invoiceNumber,
                'remarks' => $remarks !== '' ? $remarks : 'Fuel delivery recorded.',
                'created_by' => $this->currentUserId(),
            ]);

            $this->logActivity('Fuel Delivery Recorded', $deliveryId, null, [
                'invoice' => $invoiceNumber,
                'quantity_litres' => $quantity,
                'new_balance' => $newBalance,
            ]);
        });
    }

    public function adjustStock(array $data): void
    {
        $this->boot();
        $fuelTypeId = (int)($data['fuel_type_id'] ?? 0);
        if ($this->fuelTypeById($fuelTypeId) === null) throw new RuntimeException('Select a valid fuel type.');
        $quantity = (float)($data['adjustment_quantity'] ?? 0);
        if ($quantity == 0.0) throw new RuntimeException('Adjustment quantity cannot be zero.');
        $reason = trim((string)($data['reason'] ?? ''));
        if ($reason === '') throw new RuntimeException('Adjustment reason is required.');
        $this->transaction(function (Database $database) use ($fuelTypeId, $quantity, $reason): void {
            $level = $this->inventoryLevel($database, $fuelTypeId);
            $previous = (float)$level['current_stock_litres'];
            $balance = $previous + $quantity;
            if ($balance < 0) throw new RuntimeException('Stock adjustment would create a negative balance.');
            $database->update('fuel_inventory_levels', ['current_stock_litres'=>$balance,'last_updated_at'=>date('Y-m-d H:i:s')], ['id'=>(int)$level['id']]);
            $this->updateSimpleInventory($database, $fuelTypeId, $balance, $level['minimum_stock_litres']);
            $this->recordSimpleTransaction($database, $fuelTypeId, 'Adjustment', $quantity, $previous, $balance, 'ADJ-' . date('YmdHis'), $reason);
            $database->insert('fuel_inventory_movements', ['fuel_type_id'=>$fuelTypeId,'tank_id'=>null,'movement_type'=>'adjustment','quantity_litres'=>$quantity,'unit_cost'=>null,'movement_datetime'=>date('Y-m-d H:i:s'),'fuel_delivery_item_id'=>null,'fuel_sale_id'=>null,'reference'=>'ADJ-' . date('YmdHis'),'remarks'=>$reason,'created_by'=>$this->currentUserId()]);
            $this->logActivity('Fuel Stock Adjusted', $fuelTypeId, ['balance'=>$previous], ['balance'=>$balance,'quantity'=>$quantity,'reason'=>$reason]);
        });
    }

    public function deleteDelivery(int $deliveryId): void
    {
        $this->boot();
        $record = $this->deliveryRecord($deliveryId);
        if ($record === null) throw new RuntimeException('Fuel delivery was not found.');
        $this->transaction(function (Database $database) use ($record, $deliveryId): void {
            $level = $this->inventoryLevel($database, (int)$record['fuel_type_id']);
            $previous = (float)$level['current_stock_litres'];
            $balance = $previous - (float)$record['quantity_litres'];
            if ($balance < 0) throw new RuntimeException('This delivery cannot be deleted because some of its stock has already been consumed. Use a stock adjustment instead.');
            $database->update('fuel_inventory_levels', ['current_stock_litres'=>$balance,'last_updated_at'=>date('Y-m-d H:i:s')], ['id'=>(int)$level['id']]);
            $this->updateSimpleInventory($database, (int)$record['fuel_type_id'], $balance, $level['minimum_stock_litres']);
            $database->update('fuel_deliveries', ['deleted_at'=>date('Y-m-d H:i:s')], ['id'=>$deliveryId]);
            $database->update('fuel_inventory_movements', ['quantity_litres'=>0,'remarks'=>'Delivery deleted: ' . (string)$record['invoice_number']], ['fuel_delivery_item_id'=>(int)$record['item_id']]);
            $this->recordSimpleTransaction($database, (int)$record['fuel_type_id'], 'Adjustment', -(float)$record['quantity_litres'], $previous, $balance, (string)$record['invoice_number'], 'Delivery deleted.');
            $this->logActivity('Fuel Delivery Deleted', $deliveryId, $record, ['deleted_at'=>date('Y-m-d H:i:s'),'balance'=>$balance]);
        });
    }

    private function updateDelivery(int $deliveryId, array $data): void
    {
        $record = $this->deliveryRecord($deliveryId);
        if ($record === null) throw new RuntimeException('Fuel delivery was not found.');
        $fuelTypeId = (int)($data['fuel_type_id'] ?? 0);
        if ($this->fuelTypeById($fuelTypeId) === null) throw new RuntimeException('Select a valid fuel type.');
        $quantity = $this->positiveNumber($data['quantity_litres'] ?? null, 'Quantity delivered');
        $cost = $this->positiveNumber($data['cost_per_litre'] ?? null, 'Cost per litre');
        $supplier = trim((string)($data['supplier_name'] ?? ''));
        $invoice = strtoupper(trim((string)($data['invoice_number'] ?? '')));
        $tanker = trim((string)($data['tanker_number'] ?? ''));
        if ($supplier === '' || $invoice === '' || $tanker === '') throw new RuntimeException('Supplier, tanker, and delivery reference are required.');
        $duplicate = $this->database()->value('SELECT id FROM fuel_deliveries WHERE invoice_number=:invoice AND id<>:id AND deleted_at IS NULL LIMIT 1', ['invoice'=>$invoice,'id'=>$deliveryId]);
        if ($duplicate !== null) throw new RuntimeException('Delivery reference number already exists.');
        $dateTime = date('Y-m-d H:i:s', strtotime((string)($data['delivery_date'] ?? '') . ' ' . (string)($data['delivery_time'] ?? '00:00')));
        $remarks = trim((string)($data['remarks'] ?? ''));
        $this->transaction(function (Database $database) use ($record,$deliveryId,$fuelTypeId,$quantity,$cost,$supplier,$invoice,$tanker,$dateTime,$remarks,$data): void {
            $oldLevel = $this->inventoryLevel($database, (int)$record['fuel_type_id']);
            $oldBalance = (float)$oldLevel['current_stock_litres'] - (float)$record['quantity_litres'];
            if ($oldBalance < 0) throw new RuntimeException('This delivery cannot be reduced because some stock has already been consumed. Use a stock adjustment instead.');
            $database->update('fuel_inventory_levels', ['current_stock_litres'=>$oldBalance,'last_updated_at'=>date('Y-m-d H:i:s')], ['id'=>(int)$oldLevel['id']]);
            $this->updateSimpleInventory($database, (int)$record['fuel_type_id'], $oldBalance, $oldLevel['minimum_stock_litres']);
            $newLevel = $this->inventoryLevel($database, $fuelTypeId);
            $newBalance = (float)$newLevel['current_stock_litres'] + $quantity;
            $database->update('fuel_inventory_levels', ['current_stock_litres'=>$newBalance,'last_delivery_at'=>$dateTime,'last_updated_at'=>date('Y-m-d H:i:s')], ['id'=>(int)$newLevel['id']]);
            $this->updateSimpleInventory($database, $fuelTypeId, $newBalance, $newLevel['minimum_stock_litres']);
            $database->update('fuel_deliveries', ['supplier_id'=>$this->supplierId($database,$supplier),'delivery_datetime'=>$dateTime,'tanker_number'=>$tanker,'invoice_number'=>$invoice,'received_by'=>(int)($data['received_by']??0)?:null,'remarks'=>$remarks?:null], ['id'=>$deliveryId]);
            $database->update('fuel_delivery_items', ['fuel_type_id'=>$fuelTypeId,'quantity_litres'=>$quantity,'cost_per_litre'=>$cost], ['id'=>(int)$record['item_id']]);
            $database->update('fuel_inventory_movements', ['fuel_type_id'=>$fuelTypeId,'quantity_litres'=>$quantity,'unit_cost'=>$cost,'movement_datetime'=>$dateTime,'reference'=>$invoice,'remarks'=>$remarks?:'Fuel delivery updated.'], ['fuel_delivery_item_id'=>(int)$record['item_id']]);
            $this->logActivity('Fuel Delivery Updated', $deliveryId, $record, ['invoice'=>$invoice,'quantity_litres'=>$quantity,'new_balance'=>$newBalance]);
        });
    }

    private function deliveryRecord(int $id): ?array
    {
        return $this->queryOne('SELECT fd.*, fdi.id AS item_id, fdi.fuel_type_id, fdi.quantity_litres, fdi.cost_per_litre FROM fuel_deliveries fd INNER JOIN fuel_delivery_items fdi ON fdi.fuel_delivery_id=fd.id WHERE fd.id=:id AND fd.deleted_at IS NULL LIMIT 1', ['id'=>$id]);
    }

    public function recordVerifiedSaleTransaction(Database $database, array $sale, float $previousBalance, float $newBalance): void
    {
        $this->ensureCompatibilityTablesOnly();
        $fuelTypeId = (int) $sale['fuel_type_id'];
        $litres = (float) $sale['litres_sold'];
        $this->updateSimpleInventory($database, $fuelTypeId, $newBalance, null);
        $this->recordSimpleTransaction($database, $fuelTypeId, 'Sale Adjustment', -abs($litres), $previousBalance, $newBalance, (string) $sale['sale_code'], 'Automatic deduction after verified fuel sale.');
    }

    private function currentInventory(): array
    {
        $rows = $this->query("SELECT ft.id AS fuel_type_id, ft.name, ft.short_name, fil.current_stock_litres, fil.minimum_stock_litres, fil.last_delivery_at, fil.last_updated_at FROM fuel_types ft LEFT JOIN fuel_inventory_levels fil ON fil.id = (SELECT fil2.id FROM fuel_inventory_levels fil2 WHERE fil2.fuel_type_id = ft.id ORDER BY fil2.tank_id IS NULL DESC, fil2.id ASC LIMIT 1) WHERE ft.deleted_at IS NULL AND ft.status = 'active' AND ft.short_name IN ('PMS', 'AGO', 'LPG') ORDER BY FIELD(ft.short_name, 'PMS', 'AGO', 'LPG'), ft.name");
        $todaySales = $this->todayMovementTotals('sale');
        $todayDeliveries = $this->todayMovementTotals('delivery');

        return array_map(function (array $row) use ($todaySales, $todayDeliveries): array {
            $remaining = (float) ($row['current_stock_litres'] ?? 0);
            $minimum = (float) ($row['minimum_stock_litres'] ?? 0);
            $short = (string) $row['short_name'];
            return [
                'fuel_type_id' => (int) $row['fuel_type_id'],
                'fuel' => $row['name'] . ' (' . $short . ')',
                'short' => (string) $row['name'],
                'current_stock' => $remaining,
                'minimum_stock' => $minimum,
                'todays_sales' => abs((float) ($todaySales[(int) $row['fuel_type_id']] ?? 0)),
                'delivered_today' => (float) ($todayDeliveries[(int) $row['fuel_type_id']] ?? 0),
                'remaining_stock' => $remaining,
                'last_delivery' => (string) ($row['last_delivery_at'] ?? ''),
                'last_updated' => (string) ($row['last_updated_at'] ?? $row['last_delivery_at'] ?? ''),
                'tone' => $this->toneForFuel($short),
                'icon' => $this->iconForFuel($short),
            ];
        }, $rows);
    }

    private function deliveryHistory(): array
    {
        $rows = $this->query("SELECT fd.id, fd.delivery_datetime, fd.tanker_number, fd.invoice_number, fd.remarks, fd.received_by AS received_by_id, fs.name AS supplier_name, fdi.fuel_type_id, fdi.quantity_litres, fdi.cost_per_litre, ft.name AS fuel_name, ft.short_name, COALESCE(CONCAT(e.first_name, ' ', e.last_name), 'System User') AS received_by FROM fuel_deliveries fd INNER JOIN fuel_suppliers fs ON fs.id = fd.supplier_id INNER JOIN fuel_delivery_items fdi ON fdi.fuel_delivery_id = fd.id INNER JOIN fuel_types ft ON ft.id = fdi.fuel_type_id LEFT JOIN employees e ON e.id = fd.received_by WHERE fd.deleted_at IS NULL ORDER BY fd.delivery_datetime DESC, fd.id DESC LIMIT 100");

        return array_map(static function (array $row): array {
            $timestamp = strtotime((string) $row['delivery_datetime']) ?: time();
            $quantity = (float) $row['quantity_litres'];
            $cost = (float) $row['cost_per_litre'];
            return [
                'id' => (int)$row['id'],
                'fuel_type_id' => (int)$row['fuel_type_id'],
                'received_by_id' => (int)($row['received_by_id'] ?? 0),
                'remarks' => (string)($row['remarks'] ?? ''),
                'date' => date('Y-m-d', $timestamp),
                'time' => date('H:i:s', $timestamp),
                'fuel' => $row['fuel_name'] . ' (' . $row['short_name'] . ')',
                'supplier' => (string) $row['supplier_name'],
                'tanker' => (string) $row['tanker_number'],
                'invoice' => (string) $row['invoice_number'],
                'quantity' => $quantity,
                'cost_per_liter' => $cost,
                'total_cost' => $quantity * $cost,
                'received_by' => (string) $row['received_by'],
            ];
        }, $rows);
    }

    private function movementSummary(): array
    {
        $rows = $this->query("SELECT ft.id, ft.name, ft.short_name, COALESCE(SUM(CASE WHEN fim.movement_type = 'delivery' THEN fim.quantity_litres ELSE 0 END), 0) AS delivered, COALESCE(SUM(CASE WHEN fim.movement_type = 'sale' THEN ABS(fim.quantity_litres) ELSE 0 END), 0) AS sold, COALESCE(fil.current_stock_litres, 0) AS current_stock FROM fuel_types ft LEFT JOIN fuel_inventory_movements fim ON fim.fuel_type_id = ft.id LEFT JOIN fuel_inventory_levels fil ON fil.id = (SELECT fil2.id FROM fuel_inventory_levels fil2 WHERE fil2.fuel_type_id = ft.id ORDER BY fil2.tank_id IS NULL DESC, fil2.id ASC LIMIT 1) WHERE ft.deleted_at IS NULL AND ft.status = 'active' AND ft.short_name IN ('PMS', 'AGO', 'LPG') GROUP BY ft.id, ft.name, ft.short_name, fil.current_stock_litres ORDER BY FIELD(ft.short_name, 'PMS', 'AGO', 'LPG'), ft.name");

        return array_map(static function (array $row): array {
            $delivered = (float) $row['delivered'];
            $sold = (float) $row['sold'];
            $current = (float) $row['current_stock'];
            return [
                'fuel' => $row['name'] . ' (' . $row['short_name'] . ')',
                'opening_stock' => max(0, $current - $delivered + $sold),
                'delivered' => $delivered,
                'sold' => $sold,
                'remaining_stock' => $current,
            ];
        }, $rows);
    }

    private function chartData(): array
    {
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $months[] = date('M', strtotime('-' . $i . ' months'));
        }

        return [
            'monthlyDeliveries' => $this->movementChart('delivery', $months, 'Y-m', 5),
            'consumptionTrend' => $this->movementChart('sale', array_map(static fn (int $i): string => date('D', strtotime('-' . $i . ' days')), range(6, 0)), 'Y-m-d', 6),
            'distribution' => ['labels' => array_column($this->currentInventory(), 'short'), 'values' => array_column($this->currentInventory(), 'remaining_stock')],
        ];
    }

    private function movementChart(string $type, array $labels, string $dateFormat, int $span): array
    {
        $start = $dateFormat === 'Y-m' ? date('Y-m-01', strtotime('-' . $span . ' months')) : date('Y-m-d', strtotime('-' . $span . ' days'));
        $rows = $this->query("SELECT ft.short_name, DATE_FORMAT(fim.movement_datetime, :format) AS bucket, SUM(ABS(fim.quantity_litres)) AS litres FROM fuel_inventory_movements fim INNER JOIN fuel_types ft ON ft.id = fim.fuel_type_id WHERE fim.movement_type = :type AND fim.movement_datetime >= :start GROUP BY ft.short_name, bucket", ['format' => $dateFormat === 'Y-m' ? '%Y-%m' : '%Y-%m-%d', 'type' => $type, 'start' => $start]);
        $series = ['petrol' => array_fill(0, count($labels), 0), 'diesel' => array_fill(0, count($labels), 0), 'gas' => array_fill(0, count($labels), 0)];
        $buckets = [];
        foreach ($labels as $index => $_label) {
            $offset = count($labels) - 1 - $index;
            $buckets[$dateFormat === 'Y-m' ? date('Y-m', strtotime('-' . $offset . ' months')) : date('Y-m-d', strtotime('-' . $offset . ' days'))] = $index;
        }
        foreach ($rows as $row) {
            $key = match ((string) $row['short_name']) { 'PMS' => 'petrol', 'AGO' => 'diesel', default => 'gas' };
            if (isset($buckets[$row['bucket']])) {
                $series[$key][$buckets[$row['bucket']]] = (float) $row['litres'];
            }
        }

        return ['labels' => $labels, 'petrol' => $series['petrol'], 'diesel' => $series['diesel'], 'gas' => $series['gas']];
    }

    private function todayMovementTotals(string $type): array
    {
        $rows = $this->query('SELECT fuel_type_id, SUM(quantity_litres) AS total FROM fuel_inventory_movements WHERE movement_type = :type AND DATE(movement_datetime) = :today GROUP BY fuel_type_id', ['type' => $type, 'today' => date('Y-m-d')]);
        $totals = [];
        foreach ($rows as $row) {
            $totals[(int) $row['fuel_type_id']] = (float) $row['total'];
        }
        return $totals;
    }

    private function suppliers(): array
    {
        return array_column($this->query("SELECT name FROM fuel_suppliers WHERE deleted_at IS NULL AND status = 'active' ORDER BY name"), 'name');
    }

    private function receivers(): array
    {
        return $this->query("SELECT id, CONCAT(first_name, ' ', last_name) AS name FROM employees WHERE deleted_at IS NULL AND employment_status = 'active' ORDER BY first_name, last_name LIMIT 100");
    }

    private function ensureSchema(): void
    {
        $this->ensureCompatibilityTablesOnly();
        $this->database()->execute("CREATE TABLE IF NOT EXISTS fuel_suppliers (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, name VARCHAR(180) NOT NULL, phone VARCHAR(30) NULL, email VARCHAR(150) NULL, address TEXT NULL, status ENUM('active','inactive') NOT NULL DEFAULT 'active', created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, deleted_at TIMESTAMP NULL DEFAULT NULL, PRIMARY KEY (id), UNIQUE KEY uq_fuel_suppliers_name (name), KEY idx_fuel_suppliers_status (status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->database()->execute("CREATE TABLE IF NOT EXISTS fuel_inventory_levels (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, fuel_type_id BIGINT UNSIGNED NOT NULL, tank_id BIGINT UNSIGNED NULL, current_stock_litres DECIMAL(14,3) NOT NULL DEFAULT 0, minimum_stock_litres DECIMAL(14,3) NOT NULL DEFAULT 0, last_delivery_at DATETIME NULL, last_updated_at DATETIME NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (id), UNIQUE KEY uq_inventory_level_fuel_tank (fuel_type_id, tank_id), KEY idx_inventory_levels_tank (tank_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->database()->execute("CREATE TABLE IF NOT EXISTS fuel_deliveries (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, delivery_code VARCHAR(60) NOT NULL, supplier_id BIGINT UNSIGNED NOT NULL, delivery_datetime DATETIME NOT NULL, tanker_number VARCHAR(80) NOT NULL, invoice_number VARCHAR(100) NOT NULL, received_by BIGINT UNSIGNED NULL, remarks TEXT NULL, created_by BIGINT UNSIGNED NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, deleted_at TIMESTAMP NULL DEFAULT NULL, PRIMARY KEY (id), UNIQUE KEY uq_fuel_deliveries_code (delivery_code), UNIQUE KEY uq_fuel_deliveries_invoice (invoice_number), KEY idx_fuel_deliveries_supplier (supplier_id), KEY idx_fuel_deliveries_datetime (delivery_datetime)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->database()->execute("CREATE TABLE IF NOT EXISTS fuel_delivery_items (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, fuel_delivery_id BIGINT UNSIGNED NOT NULL, fuel_type_id BIGINT UNSIGNED NOT NULL, tank_id BIGINT UNSIGNED NULL, quantity_litres DECIMAL(14,3) NOT NULL, cost_per_litre DECIMAL(12,2) NOT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (id), KEY idx_delivery_items_delivery (fuel_delivery_id), KEY idx_delivery_items_fuel (fuel_type_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->database()->execute("CREATE TABLE IF NOT EXISTS fuel_inventory_movements (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, fuel_type_id BIGINT UNSIGNED NOT NULL, tank_id BIGINT UNSIGNED NULL, movement_type ENUM('opening_balance','delivery','sale','adjustment','loss') NOT NULL, quantity_litres DECIMAL(14,3) NOT NULL, unit_cost DECIMAL(12,2) NULL, movement_datetime DATETIME NOT NULL, fuel_delivery_item_id BIGINT UNSIGNED NULL, fuel_sale_id BIGINT UNSIGNED NULL, reference VARCHAR(120) NULL, remarks TEXT NULL, created_by BIGINT UNSIGNED NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (id), KEY idx_inventory_movements_fuel_date (fuel_type_id, movement_datetime), KEY idx_inventory_movements_sale (fuel_sale_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    private function ensureCompatibilityTablesOnly(): void
    {
        $this->database()->execute("CREATE TABLE IF NOT EXISTS fuel_inventory (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, fuel_type ENUM('Petrol','Diesel','Gas') NOT NULL, available_litres DECIMAL(15,2) NOT NULL DEFAULT 0, reorder_level DECIMAL(15,2) NOT NULL DEFAULT 0, last_updated TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (id), UNIQUE KEY uq_fuel_inventory_type (fuel_type)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->database()->execute("CREATE TABLE IF NOT EXISTS fuel_inventory_transactions (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, fuel_type ENUM('Petrol','Diesel','Gas') NOT NULL, transaction_type ENUM('Delivery','Sale Adjustment','Manual Adjustment') NOT NULL, litres DECIMAL(15,2) NOT NULL, previous_balance DECIMAL(15,2) NOT NULL, new_balance DECIMAL(15,2) NOT NULL, reference_no VARCHAR(100) NULL, remarks TEXT NULL, created_by BIGINT UNSIGNED NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (id), KEY idx_inventory_transactions_fuel_date (fuel_type, created_at), KEY idx_inventory_transactions_reference (reference_no)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    private function ensureFuelTypes(): void
    {
        $this->database()->execute("CREATE TABLE IF NOT EXISTS fuel_types (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, name VARCHAR(100) NOT NULL, short_name VARCHAR(30) NOT NULL, unit VARCHAR(20) NOT NULL DEFAULT 'litre', description TEXT NULL, status ENUM('active','inactive') NOT NULL DEFAULT 'active', created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, deleted_at TIMESTAMP NULL DEFAULT NULL, PRIMARY KEY (id), UNIQUE KEY uq_fuel_types_name (name), UNIQUE KEY uq_fuel_types_short (short_name), KEY idx_fuel_types_status (status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        foreach (self::FUEL_TYPES as $fuel) {
            $exists = $this->database()->value('SELECT id FROM fuel_types WHERE short_name = :short_name LIMIT 1', ['short_name' => $fuel['short_name']]);
            if ($exists === null) {
                $this->insert('fuel_types', ['name' => $fuel['name'], 'short_name' => $fuel['short_name'], 'status' => 'active']);
            }
        }
    }

    private function ensureInventoryRows(): void
    {
        foreach (self::FUEL_TYPES as $fuel) {
            $fuelType = $this->queryOne('SELECT id, name FROM fuel_types WHERE short_name = :short_name LIMIT 1', ['short_name' => $fuel['short_name']]);
            if ($fuelType === null) {
                continue;
            }
            $level = $this->queryOne('SELECT id FROM fuel_inventory_levels WHERE fuel_type_id = :fuel_type_id AND tank_id IS NULL LIMIT 1', ['fuel_type_id' => (int) $fuelType['id']]);
            if ($level === null) {
                $this->insert('fuel_inventory_levels', ['fuel_type_id' => (int) $fuelType['id'], 'tank_id' => null, 'current_stock_litres' => 0, 'minimum_stock_litres' => $fuel['reorder'], 'last_updated_at' => date('Y-m-d H:i:s')]);
            }
            $simple = $this->queryOne('SELECT id FROM fuel_inventory WHERE fuel_type = :fuel_type LIMIT 1', ['fuel_type' => $fuel['name']]);
            if ($simple === null) {
                $this->insert('fuel_inventory', ['fuel_type' => $fuel['name'], 'available_litres' => 0, 'reorder_level' => $fuel['reorder']]);
            }
        }
    }

    private function syncSimpleInventoryFromLevels(): void
    {
        foreach ($this->query("SELECT ft.name, COALESCE(SUM(fil.current_stock_litres), 0) AS stock, COALESCE(MAX(fil.minimum_stock_litres), 0) AS reorder_level FROM fuel_types ft LEFT JOIN fuel_inventory_levels fil ON fil.fuel_type_id = ft.id WHERE ft.deleted_at IS NULL AND ft.short_name IN ('PMS', 'AGO', 'LPG') GROUP BY ft.id, ft.name") as $row) {
            $this->database()->execute('INSERT INTO fuel_inventory (fuel_type, available_litres, reorder_level) VALUES (:fuel_type, :stock, :reorder_level) ON DUPLICATE KEY UPDATE available_litres = VALUES(available_litres), reorder_level = VALUES(reorder_level)', ['fuel_type' => $this->simpleFuelName((string) $row['name']), 'stock' => (float) $row['stock'], 'reorder_level' => (float) $row['reorder_level']]);
        }
    }

    private function inventoryLevel(Database $database, int $fuelTypeId): array
    {
        $level = $database->selectOne('SELECT id, current_stock_litres, minimum_stock_litres FROM fuel_inventory_levels WHERE fuel_type_id = :fuel_type_id ORDER BY tank_id IS NULL DESC, id ASC LIMIT 1', ['fuel_type_id' => $fuelTypeId]);
        if ($level === null) {
            $database->insert('fuel_inventory_levels', ['fuel_type_id' => $fuelTypeId, 'tank_id' => null, 'current_stock_litres' => 0, 'minimum_stock_litres' => 0, 'last_updated_at' => date('Y-m-d H:i:s')]);
            $level = $database->selectOne('SELECT id, current_stock_litres, minimum_stock_litres FROM fuel_inventory_levels WHERE fuel_type_id = :fuel_type_id ORDER BY id DESC LIMIT 1', ['fuel_type_id' => $fuelTypeId]);
        }
        return $level ?? ['id' => 0, 'current_stock_litres' => 0, 'minimum_stock_litres' => 0];
    }

    private function updateSimpleInventory(Database $database, int $fuelTypeId, float $balance, mixed $reorderLevel): void
    {
        $fuelType = $this->fuelTypeById($fuelTypeId);
        if ($fuelType === null) {
            return;
        }

        $fuelName = $this->simpleFuelName((string) $fuelType['name']);
        if ($reorderLevel === null) {
            $reorderLevel = $database->value('SELECT reorder_level FROM fuel_inventory WHERE fuel_type = :fuel_type LIMIT 1', ['fuel_type' => $fuelName]) ?? 0;
        }

        $database->execute('INSERT INTO fuel_inventory (fuel_type, available_litres, reorder_level) VALUES (:fuel_type, :balance, :reorder_level) ON DUPLICATE KEY UPDATE available_litres = VALUES(available_litres), reorder_level = VALUES(reorder_level)', ['fuel_type' => $fuelName, 'balance' => $balance, 'reorder_level' => (float) $reorderLevel]);
    }

    private function recordSimpleTransaction(Database $database, int $fuelTypeId, string $type, float $litres, float $previous, float $new, string $reference, string $remarks): void
    {
        $fuelType = $this->fuelTypeById($fuelTypeId);
        if ($fuelType === null) {
            return;
        }
        $database->insert('fuel_inventory_transactions', ['fuel_type' => $this->simpleFuelName((string) $fuelType['name']), 'transaction_type' => $type, 'litres' => $litres, 'previous_balance' => $previous, 'new_balance' => $new, 'reference_no' => $reference, 'remarks' => $remarks !== '' ? $remarks : null, 'created_by' => $this->currentUserId()]);
    }

    private function supplierId(Database $database, string $name): int
    {
        $existing = $database->value('SELECT id FROM fuel_suppliers WHERE name = :name AND deleted_at IS NULL LIMIT 1', ['name' => $name]);
        if ($existing !== null) {
            return (int) $existing;
        }
        return (int) $database->insert('fuel_suppliers', ['name' => $name, 'status' => 'active']);
    }

    private function fuelTypeById(int $id): ?array
    {
        return $this->queryOne('SELECT id, name, short_name FROM fuel_types WHERE id = :id AND deleted_at IS NULL LIMIT 1', ['id' => $id]);
    }

    private function positiveNumber(mixed $value, string $label): float
    {
        $clean = preg_replace('/[^0-9.\-]/', '', (string) $value);
        if ($clean === '' || !is_numeric($clean) || (float) $clean <= 0) {
            throw new RuntimeException($label . ' must be greater than zero.');
        }
        return round((float) $clean, 3);
    }

    private function currentUserId(): ?int
    {
        $userId = Session::get('auth.user_id');
        return $userId === null ? null : (int) $userId;
    }

    private function deliveryCode(): string
    {
        return 'FD-' . date('YmdHis') . '-' . random_int(100, 999);
    }

    private function simpleFuelName(string $name): string
    {
        $normalized = strtoupper(trim($name));
        if (str_contains($normalized, 'AGO') || str_contains($normalized, 'DIESEL')) {
            return 'Diesel';
        }
        if (str_contains($normalized, 'LPG') || str_contains($normalized, 'GAS')) {
            return 'Gas';
        }
        return 'Petrol';
    }

    private function toneForFuel(string $short): string
    {
        return match ($short) { 'AGO' => 'warning', 'LPG' => 'primary', default => 'success' };
    }

    private function iconForFuel(string $short): string
    {
        return match ($short) { 'AGO' => 'fa-solid fa-oil-can', 'LPG' => 'fa-solid fa-fire-flame-simple', default => 'fa-solid fa-gas-pump' };
    }

    private function logActivity(string $activity, int $entityId, mixed $oldValue, mixed $newValue): void
    {
        try {
            $this->insert('activity_logs', [
                'log_code' => 'ACT-' . date('YmdHis') . '-' . random_int(100, 999),
                'user_id' => $this->currentUserId(),
                'activity_type' => $activity,
                'module' => 'Fuel Inventory',
                'activity' => $activity,
                'entity_type' => 'fuel_inventory',
                'entity_id' => $entityId,
                'old_value' => $oldValue === null ? null : json_encode($oldValue, JSON_THROW_ON_ERROR),
                'new_value' => json_encode($newValue, JSON_THROW_ON_ERROR),
                'status' => 'success',
            ]);
        } catch (Throwable) {
        }
    }
}









