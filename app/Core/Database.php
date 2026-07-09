<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

class Database
{
    private static ?PDO $connection = null;

    public static function connection(?string $name = null): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $config = new Config(CONFIG_PATH);
        $connectionName = $name ?? (string) $config->get('database.default', 'mysql');
        $settings = $config->get("database.connections.{$connectionName}");

        if (!is_array($settings)) {
            throw new \RuntimeException("Database connection [{$connectionName}] is not configured.");
        }

        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s;charset=%s',
            $settings['driver'],
            $settings['host'],
            $settings['port'],
            $settings['database'],
            $settings['charset']
        );

        self::$connection = new PDO($dsn, (string) $settings['username'], (string) $settings['password'], $settings['options'] ?? []);

        return self::$connection;
    }
}