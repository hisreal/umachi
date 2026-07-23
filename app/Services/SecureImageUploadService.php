<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class SecureImageUploadService
{
    private const MIME_EXTENSIONS = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/webp' => ['webp'],
    ];

    private const DIRECTORIES = [
        'profile' => 'uploads/profile',
        'passport' => 'uploads/passport',
        'attendance' => 'uploads/attendance',
        'meter' => 'uploads/meter',
        'documents' => 'uploads/documents',
    ];

    public function store(mixed $file, string $category, int $maxBytes, int $maxWidth, int $maxHeight = 0): string
    {
        if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            throw new RuntimeException('Please choose an image to upload.');
        }
        if ((int) ($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('The image upload could not be processed.');
        }
        if (!isset(self::DIRECTORIES[$category])) {
            throw new RuntimeException('The image upload category is invalid.');
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > $maxBytes) {
            throw new RuntimeException('The processed image must not exceed ' . $this->formatBytes($maxBytes) . '.');
        }

        $temporaryPath = (string) ($file['tmp_name'] ?? '');
        if ($temporaryPath === '' || !is_uploaded_file($temporaryPath) || !is_file($temporaryPath)) {
            throw new RuntimeException('The image upload is invalid.');
        }

        $originalName = (string) ($file['name'] ?? '');
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($extension === '' || in_array($extension, ['php', 'php3', 'php4', 'php5', 'phtml', 'phar', 'exe', 'dll', 'com', 'bat', 'cmd', 'sh', 'svg', 'svgz'], true)) {
            throw new RuntimeException('Executable, PHP, and SVG files are not allowed.');
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($temporaryPath) ?: '';
        if (!isset(self::MIME_EXTENSIONS[$mime]) || !in_array($extension, self::MIME_EXTENSIONS[$mime], true)) {
            throw new RuntimeException('The file extension does not match a permitted JPG, PNG, or WEBP image.');
        }

        $imageInfo = @getimagesize($temporaryPath);
        if (!is_array($imageInfo) || (string) ($imageInfo['mime'] ?? '') !== $mime) {
            throw new RuntimeException('The uploaded file is not a valid image or is corrupted.');
        }
        $width = (int) ($imageInfo[0] ?? 0);
        $height = (int) ($imageInfo[1] ?? 0);
        $resolvedMaxHeight = $maxHeight > 0 ? $maxHeight : $maxWidth;
        if ($width < 50 || $height < 50 || $width > $maxWidth || $height > $resolvedMaxHeight) {
            throw new RuntimeException("Image dimensions must be between 50x50 and {$maxWidth}x{$resolvedMaxHeight} pixels.");
        }

        $bytes = file_get_contents($temporaryPath);
        if ($bytes === false || $bytes === '') {
            throw new RuntimeException('The image is empty or corrupted.');
        }
        $probe = strtolower(substr($bytes, 0, 262144));
        if (str_starts_with($bytes, 'MZ') || str_contains($probe, '<?php') || str_contains($probe, '<script') || str_contains($probe, '<svg')) {
            throw new RuntimeException('The image contains prohibited executable or script content.');
        }
        if (function_exists('imagecreatefromstring')) {
            $decoded = @imagecreatefromstring($bytes);
            if ($decoded === false) {
                throw new RuntimeException('The image could not be decoded and may be corrupted.');
            }
            imagedestroy($decoded);
        }

        $relativeDirectory = self::DIRECTORIES[$category];
        $absoluteDirectory = BASE_PATH . '/public/' . $relativeDirectory;
        if (!is_dir($absoluteDirectory) && !mkdir($absoluteDirectory, 0755, true) && !is_dir($absoluteDirectory)) {
            throw new RuntimeException('The secure image directory could not be created.');
        }

        $storedExtension = self::MIME_EXTENSIONS[$mime][0];
        $filename = bin2hex(random_bytes(24)) . '.' . $storedExtension;
        $target = $absoluteDirectory . DIRECTORY_SEPARATOR . $filename;
        if (!move_uploaded_file($temporaryPath, $target)) {
            throw new RuntimeException('The secure image could not be saved.');
        }
        @chmod($target, 0644);

        return $relativeDirectory . '/' . $filename;
    }

    private function formatBytes(int $bytes): string
    {
        return $bytes >= 1024 * 1024
            ? number_format($bytes / (1024 * 1024), 1) . ' MB'
            : number_format($bytes / 1024) . ' KB';
    }
}
