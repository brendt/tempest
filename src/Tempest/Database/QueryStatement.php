<?php

declare(strict_types=1);

namespace Tempest\Database;

use RuntimeException;
use Stringable;
use Tempest\Database\Drivers\MySqlDriver;
use Tempest\Database\Drivers\PostgreSqlDriver;
use Tempest\Database\Drivers\SQLiteDriver;
use UnhandledMatchError;

final class QueryStatement implements Stringable
{
    public function __construct(
        private readonly DatabaseDriver $driver, // @phpstan-ignore-line
        private readonly string         $table = '',
        private array                   $query = [],
    ) {
    }

    public function createTable(): self
    {
        if (! empty($this->query)) {
            throw new RuntimeException('create statement should be the first statement');
        }

        $this->query[] = sprintf("CREATE TABLE %s (", $this->table);

        return $this;
    }

    /** @throws UnhandledMatchError */
    public function alterTable(string $action): self
    {
        if (! empty($this->query)) {
            throw new RuntimeException('alter statement should be the first statement');
        }

        $operation = match ($this->driver::class) {
            MySqlDriver::class => sprintf('%s', strtoupper($action)),
            PostgreSqlDriver::class,
            SQLiteDriver::class => sprintf('%s COLUMN', strtoupper($action)),
        };

        $this->query[] = sprintf(
            "ALTER TABLE %s %s ",
            $this->table,
            $operation,
        );

        return $this;
    }

    /** @throws UnhandledMatchError */
    public function primary($key = 'id'): self
    {
        $this->query[] = match ($this->driver::class) {
            MySqlDriver::class => sprintf('%s INTEGER PRIMARY KEY AUTO_INCREMENT', $key),
            PostgreSqlDriver::class => sprintf('%s SERIAL PRIMARY KEY', $key),
            SQLiteDriver::class => sprintf('%s INTEGER PRIMARY KEY AUTOINCREMENT', $key),
        };

        return $this;
    }

    /** @throws UnhandledMatchError */
    public function createForeignKey(string $localKey, string $table, string $key = 'id', string $onDelete = 'ON DELETE CASCADE', string $onUpdate = 'ON UPDATE NO ACTION'): self
    {
        $this->query[] = match ($this->driver::class) {
            MySqlDriver::class,
            PostgreSqlDriver::class => sprintf(
                'CONSTRAINT fk_%s_%s FOREIGN KEY %s(%s) REFERENCES %s(%s) %s %s',
                strtolower($table),
                strtolower($this->table),
                $this->table,
                $localKey,
                $table,
                $key,
                $onDelete,
                $onUpdate
            ),
            SQLiteDriver::class => null,
        };

        return $this;
    }

    public function createColumn(string $name, string $type, bool $nullable = false): self
    {
        $this->query[] = sprintf('%s %s %s', $name, $type, $nullable ? '' : 'NOT NULL');

        return $this;
    }

    /** @throws UnhandledMatchError */
    public function dropForeignKeyFor(string $targetTable, string $postfixKey = 'id'): self
    {
        $this->query[] = match ($this->driver::class) {
            MySqlDriver::class,
            PostgreSqlDriver::class => sprintf(
                "ALTER TABLE %s DROP FOREIGN KEY fk_%s_%s_%s;",
                $targetTable,
                strtolower($targetTable),
                strtolower($this->table),
                strtolower($targetTable) . '_' . $postfixKey,
            ),
            SQLiteDriver::class => null,
        };

        return $this;
    }

    public function dropTable(): self
    {
        $this->query[] = sprintf("DROP TABLE %s", $this->table);

        return $this;
    }

    public function statement(string $query): self
    {
        $this->query[] = $query;

        return $this;
    }

    public function __toString(): string
    {
        $queryList = array_filter($this->query);
        $start = array_shift($queryList);

        return match (true) {
            str_starts_with($start, 'CREATE') => $start . implode(', ', $queryList) . ');',
            default => $start . implode(', ', $queryList) . ';',
        };
    }

    public function toQuery(): Query
    {
        return new Query((string) $this);
    }
}
