<?php

declare(strict_types=1);

namespace App\Services;

class AttendanceVerificationService
{
    public function prepareForReview(string $photoPath, int $employeeId): void
    {
        // Future notification and AI verification integration:
        // Queue the captured image for supervisor review or facial recognition.
    }
}
