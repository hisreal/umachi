<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use DateTimeInterface;

final class ServiceDurationFormatter
{
    public static function format(string $dateJoined, ?DateTimeInterface $asOf = null): string
    {
        $joined = DateTimeImmutable::createFromFormat('!Y-m-d', trim($dateJoined));
        $errors = DateTimeImmutable::getLastErrors();
        if ($joined === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            return 'N/A';
        }

        $current = $asOf === null
            ? new DateTimeImmutable('today')
            : DateTimeImmutable::createFromInterface($asOf)->setTime(0, 0);
        if ($joined > $current) {
            return 'N/A';
        }

        $interval = $joined->diff($current);
        $parts = [];
        if ($interval->y > 0) {
            $parts[] = $interval->y . ' ' . ($interval->y === 1 ? 'Year' : 'Years');
        }
        if ($interval->m > 0) {
            $parts[] = $interval->m . ' ' . ($interval->m === 1 ? 'Month' : 'Months');
        }
        if ($interval->d > 0) {
            $parts[] = $interval->d . ' ' . ($interval->d === 1 ? 'Day' : 'Days');
        }

        return $parts === [] ? '0 Days' : implode(', ', $parts);
    }
}
