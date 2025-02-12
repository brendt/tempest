<?php

declare(strict_types=1);

namespace Tempest\Database\Tests;

use Exception;
use PHPUnit\Framework\TestCase;
use Tempest\Database\Connection;
use Tempest\Database\GenericDatabase;
use Tempest\Database\Transactions\GenericTransactionManager;

/**
 * @internal
 */
final class GenericDatabaseTest extends TestCase
{
    public function test_it_executes_transactions(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('beginTransaction')
            ->withAnyParameters()
            ->willReturn(true);
        $connection->expects($this->once())
            ->method('commit')
            ->withAnyParameters()
            ->willReturn(true);

        $database = new GenericDatabase(
            $connection,
            new GenericTransactionManager($connection),
        );

        $result = $database->withinTransaction(function () {
            return true;
        });

        $this->assertTrue($result);
    }

    public function test_it_rolls_back_transactions_on_failure(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('beginTransaction')
            ->withAnyParameters()
            ->willReturn(true);
        $connection->expects($this->once())
            ->method('rollback')
            ->withAnyParameters()
            ->willReturn(true);

        $database = new GenericDatabase(
            $connection,
            new GenericTransactionManager($connection),
        );

        $result = $database->withinTransaction(function (): never {
            throw new Exception();
        });

        $this->assertFalse($result);
    }
}
