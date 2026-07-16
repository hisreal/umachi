<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;
use Throwable;

class Database
{
    private static ?self $instance = null;
    private ?PDO $pdo = null;
    private string $connectionName;
    /** @var array<string, mixed> */
    private array $settings;

    private function __construct(?string $connectionName = null)
    {
        $config = new Config(CONFIG_PATH);
        $this->connectionName = $connectionName ?? (string) $config->get('database.default', 'mysql');
        $settings = $config->get("database.connections.{$this->connectionName}");

        if (!is_array($settings)) {
            throw new DatabaseException('The requested database connection is not configured.');
        }

        $this->settings = $settings;
    }

    public static function getInstance(?string $connectionName = null): self
    {
        if (self::$instance === null || ($connectionName !== null && self::$instance->connectionName !== $connectionName)) {
            self::$instance = new self($connectionName);
        }

        return self::$instance;
    }

    public static function connection(?string $connectionName = null): PDO
    {
        return self::getInstance($connectionName)->pdo();
    }

    public function pdo(): PDO
    {
        if ($this->pdo instanceof PDO) {
            return $this->pdo;
        }

        $this->pdo = $this->createConnection();

        return $this->pdo;
    }

    public function statement(string $sql, array $bindings = []): PDOStatement
    {
        try {
            $statement = $this->pdo()->prepare($sql);
            $this->bindValues($statement, $bindings);
            $statement->execute();

            return $statement;
        } catch (PDOException $exception) {
            $this->handleException($exception, $sql);
        }
    }

    public function select(string $sql, array $bindings = []): array
    {
        return $this->statement($sql, $bindings)->fetchAll();
    }

    public function selectOne(string $sql, array $bindings = []): ?array
    {
        $record = $this->statement($sql, $bindings)->fetch();

        return $record === false ? null : $record;
    }

    public function value(string $sql, array $bindings = []): mixed
    {
        $value = $this->statement($sql, $bindings)->fetchColumn();

        return $value === false ? null : $value;
    }

    public function execute(string $sql, array $bindings = []): int
    {
        return $this->statement($sql, $bindings)->rowCount();
    }

    public function insert(string $table, array $data): int|string
    {
        $this->guardTable($table);
        $this->guardData($data);

        $columns = array_keys($data);
        $columnList = implode(', ', array_map([$this, 'wrapIdentifier'], $columns));
        $placeholders = implode(', ', array_map(static fn (string $column): string => ':' . $column, $columns));

        $this->statement("INSERT INTO {$this->wrapIdentifier($table)} ({$columnList}) VALUES ({$placeholders})", $data);

        return $this->pdo()->lastInsertId();
    }

    public function update(string $table, array $data, array $where): int
    {
        $this->guardTable($table);
        $this->guardData($data);
        $this->guardData($where);

        $setClauses = [];
        $bindings = [];

        foreach ($data as $column => $value) {
            $key = 'set_' . $column;
            $setClauses[] = $this->wrapIdentifier((string) $column) . ' = :' . $key;
            $bindings[$key] = $value;
        }

        [$whereSql, $whereBindings] = $this->buildWhereClause($where, 'where_');
        $bindings = array_merge($bindings, $whereBindings);

        return $this->execute(
            "UPDATE {$this->wrapIdentifier($table)} SET " . implode(', ', $setClauses) . " WHERE {$whereSql}",
            $bindings
        );
    }

    public function delete(string $table, array $where): int
    {
        $this->guardTable($table);
        $this->guardData($where);

        [$whereSql, $bindings] = $this->buildWhereClause($where, 'where_');

        return $this->execute("DELETE FROM {$this->wrapIdentifier($table)} WHERE {$whereSql}", $bindings);
    }

    public function softDelete(string $table, array $where, ?int $userId = null): int
    {
        $data = ['deleted_at' => date('Y-m-d H:i:s')];

        if ($userId !== null) {
            $data['deleted_by'] = $userId;
        }

        return $this->update($table, $data, $where);
    }

    public function find(string $table, int|string $id, string $primaryKey = 'id'): ?array
    {
        $this->guardTable($table);
        $this->guardIdentifier($primaryKey);

        return $this->selectOne(
            "SELECT * FROM {$this->wrapIdentifier($table)} WHERE {$this->wrapIdentifier($primaryKey)} = :id LIMIT 1",
            ['id' => $id]
        );
    }

    public function all(string $table, array $where = [], string $orderBy = 'id', string $direction = 'ASC'): array
    {
        $this->guardTable($table);
        $this->guardIdentifier($orderBy);

        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $sql = "SELECT * FROM {$this->wrapIdentifier($table)}";
        $bindings = [];

        if ($where !== []) {
            [$whereSql, $bindings] = $this->buildWhereClause($where, 'where_');
            $sql .= " WHERE {$whereSql}";
        }

        $sql .= " ORDER BY {$this->wrapIdentifier($orderBy)} {$direction}";

        return $this->select($sql, $bindings);
    }

    public function beginTransaction(): bool
    {
        return $this->pdo()->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo()->commit();
    }

    public function rollBack(): bool
    {
        return $this->pdo()->rollBack();
    }

    public function inTransaction(): bool
    {
        return $this->pdo()->inTransaction();
    }

    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);
            $this->commit();

            return $result;
        } catch (Throwable $exception) {
            if ($this->inTransaction()) {
                $this->rollBack();
            }

            throw $exception;
        }
    }

    public function disconnect(): void
    {
        $this->pdo = null;
    }

    private function createConnection(): PDO
    {
        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s;charset=%s',
            $this->settings['driver'] ?? 'mysql',
            $this->settings['host'] ?? '127.0.0.1',
            $this->settings['port'] ?? '3306',
            $this->settings['database'] ?? '',
            $this->settings['charset'] ?? 'utf8mb4'
        );

        try {
            return new PDO(
                $dsn,
                (string) ($this->settings['username'] ?? ''),
                (string) ($this->settings['password'] ?? ''),
                $this->settings['options'] ?? []
            );
        } catch (PDOException $exception) {
            $this->handleException($exception);
        }
    }

    private function bindValues(PDOStatement $statement, array $bindings): void
    {
        foreach ($bindings as $key => $value) {
            $parameter = is_int($key) ? $key + 1 : ':' . ltrim((string) $key, ':');
            $statement->bindValue($parameter, $value, $this->parameterType($value));
        }
    }

    private function parameterType(mixed $value): int
    {
        return match (true) {
            is_int($value) => PDO::PARAM_INT,
            is_bool($value) => PDO::PARAM_BOOL,
            $value === null => PDO::PARAM_NULL,
            default => PDO::PARAM_STR,
        };
    }

    private function buildWhereClause(array $where, string $prefix): array
    {
        $clauses = [];
        $bindings = [];

        foreach ($where as $column => $value) {
            $column = (string) $column;
            $this->guardIdentifier($column);
            $key = $prefix . $column;

            if ($value === null) {
                $clauses[] = $this->wrapIdentifier($column) . ' IS NULL';
                continue;
            }

            $clauses[] = $this->wrapIdentifier($column) . ' = :' . $key;
            $bindings[$key] = $value;
        }

        return [implode(' AND ', $clauses), $bindings];
    }

    private function guardTable(string $table): void
    {
        $this->guardIdentifier($table);
    }

    private function guardData(array $data): void
    {
        if ($data === []) {
            throw new DatabaseException('Database operation data cannot be empty.');
        }

        foreach (array_keys($data) as $column) {
            $this->guardIdentifier((string) $column);
        }
    }

    private function guardIdentifier(string $identifier): void
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $identifier)) {
            throw new DatabaseException('Invalid database identifier supplied.');
        }
    }

    private function wrapIdentifier(string $identifier): string
    {
        $this->guardIdentifier($identifier);

        return '`' . $identifier . '`';
    }

    private function handleException(PDOException $exception, ?string $sql = null): void
    {
        $message = 'Database operation failed.';
        $debug = filter_var((string) env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN);

        if ($debug) {
            $context = $sql === null ? '' : ' SQL: ' . $sql;
            error_log($exception->getMessage() . $context);
        }

        throw new DatabaseException($message, 0, $exception);
    }
}

