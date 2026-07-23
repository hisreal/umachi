<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Session;

final class ProfilePhotoService
{
    public const DEFAULT_PHOTO = 'images/sample-passport.svg';

    public function store(mixed $file, string $category = 'profile'): ?string
    {
        if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        return (new SecureImageUploadService())->store($file, $category, 200 * 1024, 600);
    }

    public function delete(?string $path): void
    {
        $path = ltrim(trim((string) $path), '/\\');
        $normalized = str_replace('\\', '/', $path);
        $allowed = str_starts_with($normalized, 'uploads/profile/')
            || str_starts_with($normalized, 'uploads/passport/')
            || str_starts_with($normalized, 'uploads/employees/photos/');
        if ($path === '' || !$allowed) {
            return;
        }
        $absolutePath = realpath(BASE_PATH . '/public/' . $path);
        $publicRoot = realpath(BASE_PATH . '/public/uploads');
        if ($absolutePath === false || $publicRoot === false || !is_file($absolutePath)) {
            return;
        }
        $rootPrefix = rtrim($publicRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (str_starts_with($absolutePath, $rootPrefix)) {
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
}