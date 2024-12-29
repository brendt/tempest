<?php

declare(strict_types=1);

namespace Tempest\Database;

use PDO;
use PDOStatement;
use Tempest\Database\Connections\DatabaseConnection;
use Tempest\Database\Exceptions\ConnectionClosed;

final class PDOConnection implements Connection
{
    private PDO|null $pdo = null;

    public function __construct(private readonly DatabaseConnection $connection)
    {
    }

    public function beginTransaction(): bool
    {
        if ($this->pdo === null) {
            throw new ConnectionClosed();
        }

        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        if ($this->pdo === null) {
            throw new ConnectionClosed();
        }

        return $this->pdo->commit();
    }

    public function rollback(): bool
    {
        if ($this->pdo === null) {
            throw new ConnectionClosed();
        }

        return $this->pdo->rollBack();
    }

    public function lastInsertId(): false|string
    {
        if ($this->pdo === null) {
            throw new ConnectionClosed();
        }

        return $this->pdo->lastInsertId();
    }

    public function prepare(string $sql): false|PDOStatement
    {
        if ($this->pdo === null) {
            throw new ConnectionClosed();
        }

        return $this->pdo->prepare($sql);
    }

    public function close(): void
    {
        $this->pdo = null;
    }

    public function connect(): void
    {
        if ($this->pdo !== null) {
            return;
        }

        $this->pdo = new PDO(
            $this->connection->getDsn(),
            $this->connection->getUsername(),
            $this->connection->getPassword(),
        );
    }
}
