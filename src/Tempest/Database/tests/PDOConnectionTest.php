<?php

declare(strict_types=1);

namespace Tempest\Database\Tests;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tempest\Database\Connections\SQLiteConnection;
use Tempest\Database\Exceptions\ConnectionClosed;
use Tempest\Database\PDOConnection;

/**
 * @internal
 */
final class PDOConnectionTest extends TestCase
{
    private const string PATH = ':memory:';

    #[DataProvider('provideQueryMethods')]
    public function test_connection_must_be_open(string $method, array $params): void
    {
        $this->expectException(ConnectionClosed::class);

        $connection = new PDOConnection(new SQLiteConnection(self::PATH));

        $connection->$method(...$params);
    }

    #[DataProvider('provideQueryMethods')]
    public function test_close_must_be_open(string $method, array $params): void
    {
        $this->expectException(ConnectionClosed::class);

        $connection = new PDOConnection(new SQLiteConnection(self::PATH));
        $connection->connect();
        $connection->close();

        $connection->$method(...$params);
    }

    public static function provideQueryMethods(): Generator
    {
        yield 'lastInsertId' => ['lastInsertId', []];
        yield 'commit' => ['commit', []];
        yield 'rollback' => ['rollback', []];
        yield 'beginTransaction' => ['beginTransaction', []];
        yield 'prepare' => ['prepare', ['select 1']];
    }

    public function test_commit(): void
    {
        $connection = new PDOConnection(new SQLiteConnection(self::PATH));
        $connection->connect();

        $this->assertTrue($connection->beginTransaction());
        $this->assertTrue($connection->commit());
    }

    public function test_rollback(): void
    {
        $connection = new PDOConnection(new SQLiteConnection(self::PATH));
        $connection->connect();

        $this->assertTrue($connection->beginTransaction());
        $this->assertTrue($connection->rollback());
    }

    public function test_last_insert_id(): void
    {
        $connection = new PDOConnection(new SQLiteConnection(self::PATH));
        $connection->connect();

        $this->assertSame('0', $connection->lastInsertId());
    }

    public function test_prepare(): void
    {
        $connection = new PDOConnection(new SQLiteConnection(self::PATH));
        $connection->connect();

        $this->assertNotFalse($connection->prepare('select 1'));
    }
}
