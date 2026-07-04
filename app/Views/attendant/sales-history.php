
 <?php          
require __DIR__ . '/../includes/header.php';
?>
<main class="clock-in-page">
            <div class="col-12">
                <article class="app-card card history-card">
                    <div class="history-toolbar">
                        <div>
                            <span class="eyebrow">Previous Shift History</span>
                            <h2>Completed Sales Records</h2>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table attendance-table align-middle">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Shift</th>
                                    <th>Pump</th>
                                    <th>Fuel Type</th>
                                    <th>Liters Sold</th>
                                    <th>Amount</th>
                                    <th>Clock Out Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($previousShiftHistory as $record): ?>
                                    <tr>
                                        <td><?php echo e($record['date']); ?></td>
                                        <td><?php echo e($record['shift']); ?></td>
                                        <td><?php echo e($record['pump']); ?></td>
                                        <td><?php echo e($record['fuel_type']); ?></td>
                                        <td><?php echo e($record['liters_sold']); ?></td>
                                        <td><?php echo e($record['amount']); ?></td>
                                        <td><?php echo e($record['clock_out_time']); ?></td>
                                        <td>
                                            <span class="table-badge table-badge--success"><?php echo e($record['status']); ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </article>
            </div>
        </div>
    </section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
