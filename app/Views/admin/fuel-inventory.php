<?php

declare(strict_types=1);

use App\Core\Session;
use App\Models\FuelInventory;

$pageTitle = 'Fuel Inventory Management | FuelOps Admin Dashboard';
$pageHeading = 'Fuel Inventory Management';
$currentRoute = 'admin/fuel-inventory';
$topbarSubtitle = 'Admin Dashboard';
$extraStyles = ['css/clock-in.css', 'css/admin-dashboard.css', 'css/fuel-inventory.css'];
$extraScripts = ['https://cdn.jsdelivr.net/npm/chart.js', 'js/admin-dashboard.js', 'js/fuel-inventory.js'];
$sidebarVariant = 'admin-sidebar';
$sidebarHomeRoute = 'admin/dashboard';
$sidebarBrandTitle = 'FuelOps';
$sidebarBrandSubtitle = 'Admin Panel';
$navItems = require __DIR__ . '/../includes/admin-nav.php';
$employee = ['name' => 'Administrator', 'role' => 'System Administrator'];
$attendantName = $employee['name'];
$attendantRole = $employee['role'];

$inventorySuccess = Session::pullFlash('inventory_success');
$inventoryError = Session::pullFlash('inventory_error');

try {
    $inventoryData = (new FuelInventory())->dashboardData();
} catch (Throwable) {
    $inventoryError = $inventoryError ?: 'Fuel inventory records could not be loaded. Please verify the database schema.';
    $inventoryData = [
        'fuelInventory' => [],
        'suppliers' => [],
        'receivers' => [],
        'deliveryHistory' => [],
        'inventoryMovements' => [],
        'lowStockItems' => [],
        'summaryCards' => [],
        'chartData' => ['monthlyDeliveries' => ['labels' => [], 'petrol' => [], 'diesel' => [], 'gas' => []], 'consumptionTrend' => ['labels' => [], 'petrol' => [], 'diesel' => [], 'gas' => []], 'distribution' => ['labels' => [], 'values' => []]],
    ];
}

$fuelInventory = $inventoryData['fuelInventory'];
$suppliers = $inventoryData['suppliers'];
$receivers = $inventoryData['receivers'];
$deliveryHistory = $inventoryData['deliveryHistory'];
$inventoryMovements = $inventoryData['inventoryMovements'];
$lowStockItems = $inventoryData['lowStockItems'];
$summaryCards = $inventoryData['summaryCards'];
$chartData = $inventoryData['chartData'];

$statusForStock = static function (float $remaining, float $minimum): array {
    if ($remaining <= 0) { return ['label' => 'Out of Stock', 'class' => 'inventory-status--out']; }
    if ($remaining <= (float) floor($minimum * 0.5)) { return ['label' => 'Critical', 'class' => 'inventory-status--critical']; }
    if ($remaining <= $minimum) { return ['label' => 'Low Stock', 'class' => 'inventory-status--low']; }
    return ['label' => 'Normal', 'class' => 'inventory-status--healthy'];
};
require __DIR__ . '/../includes/header.php';
?>
<main class="clock-in-page fuel-inventory-page" data-inventory-chart-data="<?php echo e(json_encode($chartData, JSON_HEX_APOS | JSON_HEX_QUOT)); ?>">
    <section class="clock-hero inventory-hero"><div class="container-fluid"><nav class="inventory-breadcrumb" aria-label="Breadcrumb"><a href="<?php echo e(route_url('admin/dashboard')); ?>">Dashboard</a><i class="fa-solid fa-chevron-right"></i><span>Fuel Inventory</span></nav><div class="clock-hero__content inventory-hero-card"><div><span class="eyebrow">Stock Control</span><h1>Fuel Inventory Management</h1><p>Record tanker deliveries, monitor available stock, and track stock balances after verified fuel sales deductions.</p></div><span class="inventory-hero-icon"><i class="fa-solid fa-warehouse"></i></span></div></div></section>
    <section class="container-fluid clock-workspace">
        <?php if (!empty($inventorySuccess)): ?><div class="alert alert-success"><?php echo e((string) $inventorySuccess); ?></div><?php endif; ?>
        <?php if (!empty($inventoryError)): ?><div class="alert alert-danger"><?php echo e((string) $inventoryError); ?></div><?php endif; ?>
        <div class="inventory-summary-grid">
            <?php foreach ($summaryCards as $card): ?>
                <article class="inventory-summary-card inventory-summary-card--<?php echo e($card['tone']); ?>"><span><i class="<?php echo e($card['icon']); ?>"></i></span><div><small><?php echo e($card['label']); ?></small><strong><?php echo e($card['value']); ?></strong></div></article>
            <?php endforeach; ?>
        </div>
        <div class="inventory-fuel-grid mt-4">
            <?php foreach ($fuelInventory as $item): ?>
                <?php $stockStatus = $statusForStock($item['remaining_stock'], $item['minimum_stock']); ?>
                <article class="app-card card inventory-fuel-card inventory-fuel-card--<?php echo e($item['tone']); ?>">
                    <div class="inventory-fuel-card__header"><span><i class="<?php echo e($item['icon']); ?>"></i></span><div><small><?php echo e($stockStatus['label']); ?></small><h2><?php echo e($item['fuel']); ?></h2></div></div>
                    <div class="inventory-fuel-metrics"><div><small>Current Stock</small><strong><?php echo e(number_format($item['current_stock'])); ?> L</strong></div><div><small>Today's Sales</small><strong><?php echo e(number_format($item['todays_sales'])); ?> L</strong></div><div><small>Remaining Stock</small><strong><?php echo e(number_format($item['remaining_stock'])); ?> L</strong></div></div>
                    <div class="inventory-progress" aria-label="Stock level against minimum"><?php $stockPercent = min(100, (int) round(($item['remaining_stock'] / max(1, $item['minimum_stock'] * 2)) * 100)); ?><span style="width: <?php echo e($stockPercent); ?>%"></span></div>
                </article>
            <?php endforeach; ?>
        </div>
        <div class="row g-4 mt-1 align-items-start">
            <div class="col-12 col-xl-5">
                <article class="app-card card inventory-form-card">
                    <div class="app-card__header"><div><span class="eyebrow">Tanker Delivery</span><h2>Record New Fuel Delivery</h2></div><span class="inventory-section-icon"><i class="fa-solid fa-truck-droplet"></i></span></div>
                    <form id="fuelDeliveryForm" class="inventory-form" method="post" action="<?php echo e(route_url('admin/fuel-inventory/delivery')); ?>" novalidate><?php echo csrf_field(); ?>
                        <div class="row g-3">
                            <div class="col-12 col-md-6"><label class="form-label" for="deliveryFuelType">Fuel Type</label><select class="form-select" id="deliveryFuelType" name="fuel_type_id" required><option value="">Select fuel type</option><?php foreach ($fuelInventory as $item): ?><option value="<?php echo e($item['fuel_type_id']); ?>"><?php echo e($item['fuel']); ?></option><?php endforeach; ?></select></div>
                            <div class="col-12 col-md-6"><label class="form-label" for="deliveryDate">Delivery Date</label><input class="form-control" id="deliveryDate" name="delivery_date" type="date" value="<?php echo e(date('Y-m-d')); ?>" required></div>
                            <div class="col-12 col-md-6"><label class="form-label" for="deliveryTime">Delivery Time</label><input class="form-control" id="deliveryTime" name="delivery_time" type="time" value="<?php echo e(date('H:i')); ?>" required></div>
                            <div class="col-12 col-md-6"><label class="form-label" for="supplierName">Supplier Name</label><input class="form-control" id="supplierName" name="supplier_name" type="text" list="supplierOptions" placeholder="Supplier company" required><datalist id="supplierOptions"><?php foreach ($suppliers as $supplier): ?><option value="<?php echo e($supplier); ?>"></option><?php endforeach; ?></datalist></div>
                            <div class="col-12 col-md-6"><label class="form-label" for="tankerNumber">Tanker Number</label><input class="form-control" id="tankerNumber" name="tanker_number" type="text" placeholder="e.g. PMS-4418" required></div>
                            <div class="col-12 col-md-6"><label class="form-label" for="invoiceNumber">Delivery Note / Invoice Number</label><input class="form-control" id="invoiceNumber" name="invoice_number" type="text" placeholder="Invoice number" required></div>
                            <div class="col-12 col-md-6"><label class="form-label" for="quantityDelivered">Quantity Delivered (Liters)</label><input class="form-control" id="quantityDelivered" name="quantity_litres" type="number" min="1" step="0.01" placeholder="0" required></div>
                            <div class="col-12 col-md-6"><label class="form-label" for="costPerLiter">Cost Per Liter</label><input class="form-control" id="costPerLiter" name="cost_per_litre" type="number" min="1" step="0.01" placeholder="0.00" required></div>
                            <div class="col-12 col-md-6"><label class="form-label" for="totalCost">Total Cost</label><input class="form-control" id="totalCost" type="text" value="NGN 0" readonly></div>
                            <div class="col-12 col-md-6"><label class="form-label" for="receivedBy">Received By</label><select class="form-select" id="receivedBy" name="received_by"><option value="">Current user / not assigned</option><?php foreach ($receivers as $receiver): ?><option value="<?php echo e($receiver['id']); ?>"><?php echo e($receiver['name']); ?></option><?php endforeach; ?></select></div>
                            <div class="col-12"><label class="form-label" for="deliveryRemarks">Remarks</label><textarea class="form-control" id="deliveryRemarks" name="remarks" rows="3" placeholder="Optional delivery remarks"></textarea></div>
                        </div>
                        <div class="inventory-form-actions"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i>Save Delivery</button><button class="btn btn-outline-brand" type="reset">Reset</button><button class="btn btn-light" type="button" data-inventory-action="cancel-form">Cancel</button></div>
                    </form>
                </article>
            </div>
            <div class="col-12 col-xl-7">
                <article class="app-card card inventory-table-card"><div class="app-card__header"><div><span class="eyebrow">Live Stock</span><h2>Current Fuel Stock</h2></div></div>

                    <div class="table-responsive"><table class="table attendance-table inventory-table align-middle"><thead><tr><th>Fuel Type</th><th>Current Stock</th><th>Minimum Stock Level</th><th>Stock Status</th><th>Last Delivery</th><th>Last Updated</th></tr></thead><tbody><?php foreach ($fuelInventory as $item): ?><?php $stockStatus = $statusForStock($item['remaining_stock'], $item['minimum_stock']); ?><tr><td><strong><?php echo e($item['fuel']); ?></strong></td><td><?php echo e(number_format($item['remaining_stock'])); ?> L</td><td><?php echo e(number_format($item['minimum_stock'])); ?> L</td><td><span class="table-badge <?php echo e($stockStatus['class']); ?>"><?php echo e($stockStatus['label']); ?></span></td><td><?php echo e(format_date($item['last_delivery'])); ?></td><td><?php echo e(format_datetime($item['last_updated'])); ?></td></tr><?php endforeach; ?></tbody></table></div>
                </article>
                <article class="app-card card inventory-alert-card mt-4"><div class="app-card__header"><div><span class="eyebrow">Alerts</span><h2>Low Stock Alert Panel</h2></div></div><div class="inventory-alert-list">
                    <?php if ($lowStockItems === []): ?><div class="inventory-alert inventory-alert--healthy"><span><i class="fa-solid fa-circle-check"></i></span><div><strong>All fuel types are above minimum stock level.</strong><p>No replenishment alert is active right now.</p></div></div><?php endif; ?>
                    <?php foreach ($lowStockItems as $item): ?><div class="inventory-alert"><span><i class="fa-solid fa-triangle-exclamation"></i></span><div><strong><?php echo e($item['fuel']); ?></strong><p>Remaining: <?php echo e(number_format($item['remaining_stock'])); ?> Liters</p><p>Minimum Level: <?php echo e(number_format($item['minimum_stock'])); ?> Liters</p><small>Recommendation: Order new <?php echo e($item['short']); ?> supply immediately.</small></div></div><?php endforeach; ?>
                </div></article>
            </div>
        </div>        <article class="app-card card inventory-table-card mt-4">
            <div class="inventory-toolbar"><div><span class="eyebrow">Transactions</span><h2>Fuel Delivery History</h2></div><div class="inventory-toolbar-actions"><div class="filter-control"><i class="fa-solid fa-magnifying-glass"></i><input id="inventorySearch" type="search" placeholder="Search supplier, tanker, invoice"></div><select class="form-select" id="inventoryFuelFilter" aria-label="Filter delivery history by fuel type"><option value="">All fuel types</option><?php foreach ($fuelInventory as $item): ?><option value="<?php echo e($item['fuel']); ?>"><?php echo e($item['fuel']); ?></option><?php endforeach; ?></select><div class="dropdown"><button class="btn btn-outline-brand dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa-solid fa-download"></i>Export</button><ul class="dropdown-menu dropdown-menu-end"><li><button class="dropdown-item" type="button" data-inventory-export="PDF">Export PDF</button></li><li><button class="dropdown-item" type="button" data-inventory-export="Excel">Export Excel</button></li><li><button class="dropdown-item" type="button" data-inventory-export="CSV">Export CSV</button></li></ul></div></div></div>
            <div class="table-responsive"><table class="table attendance-table inventory-table align-middle"><thead><tr><th>Delivery Date</th><th>Fuel Type</th><th>Supplier</th><th>Tanker Number</th><th>Quantity Delivered</th><th>Cost Per Liter</th><th>Total Cost</th><th>Received By</th><th>Actions</th></tr></thead><tbody id="inventoryDeliveryBody"><?php foreach ($deliveryHistory as $delivery): ?><tr data-inventory-row data-fuel="<?php echo e($delivery['fuel']); ?>" data-search="<?php echo e(strtolower($delivery['supplier'] . ' ' . $delivery['tanker'] . ' ' . $delivery['invoice'] . ' ' . $delivery['fuel'])); ?>"><td><?php echo e(format_date($delivery['date'])); ?><br><small><?php echo e(format_date($delivery['time'], 'h:i A')); ?></small></td><td><strong><?php echo e($delivery['fuel']); ?></strong></td><td><?php echo e($delivery['supplier']); ?></td><td><?php echo e($delivery['tanker']); ?></td><td><?php echo e(number_format($delivery['quantity'])); ?> L</td><td>NGN <?php echo e(number_format($delivery['cost_per_liter'], 2)); ?></td><td>NGN <?php echo e(number_format($delivery['total_cost'])); ?></td><td><?php echo e($delivery['received_by']); ?></td><td><div class="inventory-actions"><button class="btn btn-sm btn-light" type="button" data-inventory-action="view" data-delivery="<?php echo e($delivery['invoice']); ?>" title="View"><i class="fa-solid fa-eye"></i></button><button class="btn btn-sm btn-light" type="button" data-inventory-action="edit" data-delivery="<?php echo e($delivery['invoice']); ?>" title="Edit"><i class="fa-solid fa-pen-to-square"></i></button><button class="btn btn-sm btn-light" type="button" data-inventory-action="delete" data-delivery="<?php echo e($delivery['invoice']); ?>" title="Delete"><i class="fa-solid fa-trash"></i></button></div></td></tr><?php endforeach; ?></tbody></table></div>
            <div class="inventory-pagination"><span id="inventoryPageSummary">Showing delivery records</span><div><button class="btn btn-outline-brand btn-sm" id="prevInventoryPage" type="button"><i class="fa-solid fa-chevron-left"></i></button><button class="btn btn-outline-brand btn-sm" id="nextInventoryPage" type="button"><i class="fa-solid fa-chevron-right"></i></button></div></div>
        </article>
        <div class="row g-4 mt-1"><div class="col-12 col-xl-7"><article class="app-card card inventory-table-card"><div class="app-card__header"><div><span class="eyebrow">Reconciliation</span><h2>Inventory Movement Summary</h2></div></div><div class="table-responsive"><table class="table attendance-table inventory-table align-middle"><thead><tr><th>Fuel Type</th><th>Opening Stock</th><th>Delivered</th><th>Sold</th><th>Remaining Stock</th></tr></thead><tbody><?php foreach ($inventoryMovements as $movement): ?><tr><td><strong><?php echo e($movement['fuel']); ?></strong></td><td><?php echo e(number_format($movement['opening_stock'])); ?> L</td><td><?php echo e(number_format($movement['delivered'])); ?> L</td><td><?php echo e(number_format($movement['sold'])); ?> L</td><td><?php echo e(number_format($movement['remaining_stock'])); ?> L</td></tr><?php endforeach; ?></tbody></table></div></article></div><div class="col-12 col-xl-5"><article class="app-card card inventory-future-card"><div class="app-card__header"><div><span class="eyebrow">Controls</span><h2>Inventory Safeguards</h2></div></div><ul><li>Verified fuel sales deduct stock automatically.</li><li>Every delivery creates an inventory transaction.</li><li>Duplicate delivery references are rejected.</li><li>Negative stock balances are blocked.</li><li>Low stock status is calculated from reorder levels.</li><li>Inventory movements preserve an audit trail.</li><li>Dashboard charts use recorded delivery and sale movements.</li></ul></article></div></div>
        <section class="inventory-chart-grid mt-4" aria-label="Fuel inventory charts"><article class="app-card card inventory-chart-card"><h2>Monthly Fuel Deliveries</h2><canvas id="inventoryDeliveriesChart" height="280"></canvas></article><article class="app-card card inventory-chart-card"><h2>Fuel Consumption Trend</h2><canvas id="inventoryConsumptionChart" height="280"></canvas></article><article class="app-card card inventory-chart-card inventory-chart-card--wide"><h2>Current Fuel Distribution</h2><canvas id="inventoryDistributionChart" height="260"></canvas></article></section>
    </section>
</main>
<script>window.inventoryDeliveries = <?php echo json_encode($deliveryHistory, JSON_THROW_ON_ERROR); ?>;</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>


