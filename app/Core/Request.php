<?php

declare(strict_types=1);

namespace App\Core;

class Request
{
    public function __construct(
        private array $query,
        private array $post,
        private array $server,
        private array $files
    ) {
    }

    public static function capture(): self
    {
        return new self($_GET, $_POST, $_SERVER, $_FILES);
    }

    public function route(): string
    {
        return trim((string) ($this->query['route'] ?? 'dashboard'), '/');
    }

    public function method(): string
    {
        return strtoupper((string) ($this->server['REQUEST_METHOD'] ?? 'GET'));
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $this->query[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->post);
    }
}
