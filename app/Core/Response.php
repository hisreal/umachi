<?php

declare(strict_types=1);

namespace App\Core;

class Response
{
    public function setStatusCode(int $code): void
    {
        http_response_code($code);
    }

    public function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    public function json(array $payload, int $status = 200): void
    {
        $this->setStatusCode($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public function success(string $message, array $data = [], int $status = 200, array $meta = []): void
    {
        $this->json(self::payload(true, $message, $data, [], $meta), $status);
    }

    public function error(string $message, array $errors = [], int $status = 422, array $data = [], array $meta = []): void
    {
        $this->json(self::payload(false, $message, $data, $errors, $meta), $status);
    }

    private static function payload(bool $success, string $message, array $data, array $errors, array $meta): array
    {
        return ['success' => $success, 'message' => $message, 'data' => (object) $data, 'errors' => (object) $errors, 'meta' => (object) $meta];
    }
}