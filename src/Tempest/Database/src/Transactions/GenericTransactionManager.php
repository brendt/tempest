<?php

declare(strict_types=1);

namespace Tempest\Database\Transactions;

use Tempest\Database\Connection;
use Tempest\Database\Exceptions\CouldNotBeginTransaction;
use Tempest\Database\Exceptions\CouldNotCommitTransaction;
use Tempest\Database\Exceptions\CouldNotRollbackTransaction;

final class GenericTransactionManager implements TransactionManager
{
    public function __construct(private Connection $connection)
    {
    }

    public function begin(): void
    {
        $transactionBegun = $this->connection->beginTransaction();

        if (! $transactionBegun) {
            throw new CouldNotBeginTransaction();
        }
    }

    public function commit(): void
    {
        $transactionCommitted = $this->connection->commit();

        if (! $transactionCommitted) {
            throw new CouldNotCommitTransaction();
        }
    }

    public function rollback(): void
    {
        $transactionRolledBack = $this->connection->rollBack();

        if (! $transactionRolledBack) {
            throw new CouldNotRollbackTransaction();
        }
    }
}
