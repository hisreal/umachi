<?php

declare(strict_types=1);

namespace App\Core;

class Config
{
    /** @var array<string, mixed> */
    private array $items = [];

    public function __construct(private string $configPath)
    {
        $this->load();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = $this->items;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    public function all(): array
    {
        return $this->items;
    }

    private function load(): void
    {
        foreach (glob(rtrim($this->configPath, '/\\') . '/*.php') ?: [] as $file) {
            $this->items[basename($file, '.php')] = require $file;
        }
    }
}
