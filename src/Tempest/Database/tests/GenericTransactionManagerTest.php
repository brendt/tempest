<?php

declare(strict_types=1);

namespace Tempest\Database\Tests;

use PHPUnit\Framework\TestCase;
use Tempest\Database\Connection;
use Tempest\Database\Exceptions\CouldNotBeginTransaction;
use Tempest\Database\Exceptions\CouldNotCommitTransaction;
use Tempest\Database\Exceptions\CouldNotRollbackTransaction;
use Tempest\Database\Transactions\GenericTransactionManager;

/**
 * @internal
 */
final class GenericTransactionManagerTest extends TestCase
{
    public function test_it_calls_being_transactions(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('beginTransaction')
            ->withAnyParameters()
            ->willReturn(true);

        $manager = new GenericTransactionManager($connection);

        $manager->begin();
    }

    public function test_it_throws_an_exception_when_transaction_cannot_begin(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('beginTransaction')
            ->withAnyParameters()
            ->willReturn(false);

        $this->expectException(CouldNotBeginTransaction::class);

        $manager = new GenericTransactionManager($connection);

        $manager->begin();
    }

    public function test_it_calls_commit_transactions(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('commit')
            ->withAnyParameters()
            ->willReturn(true);

        $manager = new GenericTransactionManager($connection);

        $manager->commit();
    }

    public function test_it_throws_an_exception_when_transaction_cannot_commit(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('commit')
            ->withAnyParameters()
            ->willReturn(false);

        $this->expectException(CouldNotCommitTransaction::class);

        $manager = new GenericTransactionManager($connection);

        $manager->commit();
    }

    public function test_it_calls_rollback_transactions(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('rollBack')
            ->withAnyParameters()
            ->willReturn(true);

        $manager = new GenericTransactionManager($connection);

        $manager->rollback();
    }

    public function test_it_throws_an_exception_when_transaction_cannot_rollback(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('rollBack')
            ->withAnyParameters()
            ->willReturn(false);

        $this->expectException(CouldNotRollbackTransaction::class);

        $manager = new GenericTransactionManager($connection);

        $manager->rollback();
    }
}
