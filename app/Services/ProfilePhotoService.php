<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Session;
use RuntimeException;

final class ProfilePhotoService
{
    public const DEFAULT_PHOTO = 'images/sample-passport.svg';
    public const UPLOAD_DIRECTORY = 'uploads/employees/photos';
    public const MAX_FILE_SIZE = 5 * 1024 * 1024;

    private const ALLOWED_MIME_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public function store(mixed $file): ?string
    {
        if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ((int) ($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Profile photo upload failed. Please try again.');
        }
        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_FILE_SIZE) {
            throw new RuntimeException('Profile photo must not be larger than 5 MB.');
        }
        $temporaryPath = (string) ($file['tmp_name'] ?? '');
        if ($temporaryPath === '' || !is_uploaded_file($temporaryPath)) {
            throw new RuntimeException('Invalid profile photo upload.');
        }
        $mimeType = (new \finfo(FILEINFO_MIME_TYPE))->file($temporaryPath) ?: '';
        if (!isset(self::ALLOWED_MIME_TYPES[$mimeType])) {
            throw new RuntimeException('Profile photo must be a JPG, PNG, or WEBP image.');
        }
        $uploadRoot = $this->uploadRoot();
        if (!is_dir($uploadRoot) && !mkdir($uploadRoot, 0755, true) && !is_dir($uploadRoot)) {
            throw new RuntimeException('Profile photo directory could not be created.');
        }
        $filename = bin2hex(random_bytes(16)) . '.' . self::ALLOWED_MIME_TYPES[$mimeType];
        if (!move_uploaded_file($temporaryPath, $uploadRoot . DIRECTORY_SEPARATOR . $filename)) {
            throw new RuntimeException('Profile photo could not be saved.');
        }
        return self::UPLOAD_DIRECTORY . '/' . $filename;
    }

    public function delete(?string $path): void
    {
        $path = ltrim(trim((string) $path), '/\\');
        if ($path === '' || !str_starts_with(str_replace('\\', '/', $path), self::UPLOAD_DIRECTORY . '/')) {
            return;
        }
        $uploadRoot = realpath($this->uploadRoot());
        $absolutePath = realpath(BASE_PATH . '/public/' . $path);
        if ($uploadRoot === false || $absolutePath === false) {
            return;
        }
        $rootPrefix = rtrim($uploadRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (str_starts_with($absolutePath, $rootPrefix) && is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }

    public function refreshSessionAvatar(?string $photoPath): void
    {
        $authUser = Session::get('auth.user', []);
        if (is_array($authUser)) {
            $authUser['avatar'] = $photoPath;
            Session::put('auth.user', $authUser);
        }
    }

    private function uploadRoot(): string
    {
        return BASE_PATH . '/public/' . self::UPLOAD_DIRECTORY;
    }
}