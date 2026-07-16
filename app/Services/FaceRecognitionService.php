<?php

declare(strict_types=1);

namespace App\Services;

class FaceRecognitionService
{
    public function compare(string $capturedPhotoPath, ?string $profilePhotoPath): array
    {
        // Future AI integration:
        // Compare the captured attendance image with the employee's registered profile photo.
        return ['verified' => false, 'score' => null, 'message' => 'Face recognition is not enabled yet.'];
    }
}
