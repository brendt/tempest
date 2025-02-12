<?php

declare(strict_types=1);

namespace Tempest\Database\Tests;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tempest\Database\Connections\DatabaseConnection;
use Tempest\Database\Connections\MySqlConnection;
use Tempest\Database\Connections\PostgresConnection;
use Tempest\Database\Connections\SQLiteConnection;

/**
 * @internal
 */
final class DatabaseDriverTest extends TestCase
{
    #[DataProvider('provide_database_drivers')]
    #[Test]
    public function driver_has_the_correct_dsn(DatabaseConnection $driver, string $dsn, ?string $username, ?string $password): void
    {
        $this->assertSame($dsn, $driver->getDsn());
        $this->assertSame($username, $driver->getUsername());
        $this->assertSame($password, $driver->getPassword());
    }

    public static function provide_database_drivers(): Generator
    {
        yield 'sqlite' => [
            new SQLiteConnection(path: '/usr/local/db.sqlite'),
            'sqlite:/usr/local/db.sqlite',
            null,
            null,
        ];

        yield 'mysql' => [
            new MySqlConnection(
                host: 'localhost',
                port: '3307',
                username: 'user',
                password: 'secret',
                database: 'tempest',
            ),
            'mysql:host=localhost:3307;dbname=tempest',
            'user',
            'secret',
        ];

        yield 'postgresql' => [
            new PostgresConnection(
                host: 'localhost',
                port: '5432',
                username: 'postgres',
                password: 'secret',
                database: 'tempest',
            ),
            'pgsql:host=localhost;port=5432;dbname=tempest;user=postgres;password=secret',
            'postgres',
            'secret',
        ];
    }
}
