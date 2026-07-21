<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/autoload.php';

use App\Services\ServiceDurationFormatter;

$asOf = new DateTimeImmutable('2026-07-21');
$cases = [
    '2026-07-06' => '15 Days',
    '2026-05-11' => '2 Months, 10 Days',
    '2025-04-09' => '1 Year, 3 Months, 12 Days',
    '2022-05-21' => '4 Years, 2 Months',
    '2019-02-03' => '7 Years, 5 Months, 18 Days',
    '2026-07-21' => '0 Days',
    '2024-02-29' => '2 Years, 4 Months, 22 Days',
];

foreach ($cases as $joined => $expected) {
    $actual = ServiceDurationFormatter::format($joined, $asOf);
    if ($actual !== $expected) {
        throw new RuntimeException("{$joined}: expected [{$expected}], got [{$actual}]");
    }
}

foreach (['', '2026-02-30', '2027-01-01'] as $invalid) {
    if (ServiceDurationFormatter::format($invalid, $asOf) !== 'N/A') {
        throw new RuntimeException("Invalid or future date was accepted: {$invalid}");
    }
}

echo "Service duration tests passed.\n";
