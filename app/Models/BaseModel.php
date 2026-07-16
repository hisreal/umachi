<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

abstract class BaseModel
{
    protected function database(): Database
    {
        return Database::getInstance();
    }

    protected function db(): PDO
    {
        return $this->database()->pdo();
    }

    protected function query(string $sql, array $bindings = []): array
    {
        return $this->database()->select($sql, $bindings);
    }

    protected function queryOne(string $sql, array $bindings = []): ?array
    {
        return $this->database()->selectOne($sql, $bindings);
    }

    protected function insert(string $table, array $data): int|string
    {
        return $this->database()->insert($table, $data);
    }

    protected function update(string $table, array $data, array $where): int
    {
        return $this->database()->update($table, $data, $where);
    }

    protected function delete(string $table, array $where): int
    {
        return $this->database()->delete($table, $where);
    }

    protected function softDelete(string $table, array $where, ?int $userId = null): int
    {
        return $this->database()->softDelete($table, $where, $userId);
    }

    protected function transaction(callable $callback): mixed
    {
        return $this->database()->transaction($callback);
    }
}
