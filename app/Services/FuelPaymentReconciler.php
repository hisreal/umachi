<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class FuelPaymentReconciler
{
    public static function reconcile(float $expectedAmount, float $cash, float $pos, float $transfer, string $remark = ''): array
    {
        foreach (['Expected amount' => $expectedAmount, 'Cash received' => $cash, 'POS / Card payments' => $pos, 'Bank transfer' => $transfer] as $label => $amount) {
            if (!is_finite($amount)) {
                throw new RuntimeException($label . ' calculation failed.');
            }
            if ($amount < 0) {
                throw new RuntimeException($label . ' cannot be negative.');
            }
        }

        $expectedAmount = round($expectedAmount, 2);
        $cash = round($cash, 2);
        $pos = round($pos, 2);
        $transfer = round($transfer, 2);
        $total = round($cash + $pos + $transfer, 2);
        $difference = round($expectedAmount - $total, 2);
        $status = abs($difference) < 0.01 ? 'balanced' : ($difference > 0 ? 'shortage' : 'overpayment');
        $remark = trim($remark);
        if ($status !== 'balanced' && $remark === '') {
            throw new RuntimeException('A payment explanation is required when the shift payment is not balanced.');
        }

        return [
            'expected_amount' => $expectedAmount,
            'cash_received' => $cash,
            'pos_received' => $pos,
            'bank_transfer_received' => $transfer,
            'total_received' => $total,
            'difference_amount' => $difference,
            'balance_status' => $status,
            'payment_remark' => $remark,
        ];
    }
}
