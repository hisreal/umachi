<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/autoload.php';

use App\Services\FuelPaymentReconciler;

$balanced = FuelPaymentReconciler::reconcile(100000, 50000, 30000, 20000);
if ($balanced['total_received'] !== 100000.0 || $balanced['difference_amount'] !== 0.0 || $balanced['balance_status'] !== 'balanced') {
    throw new RuntimeException('Balanced reconciliation failed.');
}

$shortage = FuelPaymentReconciler::reconcile(100000, 40000, 30000, 20000, 'Customer transfer pending');
if ($shortage['total_received'] !== 90000.0 || $shortage['difference_amount'] !== 10000.0 || $shortage['balance_status'] !== 'shortage') {
    throw new RuntimeException('Shortage reconciliation failed.');
}

$overpayment = FuelPaymentReconciler::reconcile(100000, 60000, 30000, 20000, 'Excess cash recorded');
if ($overpayment['total_received'] !== 110000.0 || $overpayment['difference_amount'] !== -10000.0 || $overpayment['balance_status'] !== 'overpayment') {
    throw new RuntimeException('Overpayment reconciliation failed.');
}

$mustFail = [
    static fn (): array => FuelPaymentReconciler::reconcile(100, -1, 50, 51),
    static fn (): array => FuelPaymentReconciler::reconcile(100, 90, 0, 0),
    static fn (): array => FuelPaymentReconciler::reconcile(INF, 0, 0, 0),
];

foreach ($mustFail as $case) {
    try {
        $case();
        throw new RuntimeException('Expected payment validation failure did not occur.');
    } catch (RuntimeException $exception) {
        if ($exception->getMessage() === 'Expected payment validation failure did not occur.') {
            throw $exception;
        }
    }
}

echo "Fuel payment reconciliation tests passed.\n";
