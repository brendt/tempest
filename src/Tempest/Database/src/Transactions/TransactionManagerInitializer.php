<?php

declare(strict_types=1);

namespace Tempest\Database\Transactions;

use Tempest\Container\Container;
use Tempest\Container\Initializer;
use Tempest\Container\Singleton;
use Tempest\Database\Connection;

final readonly class TransactionManagerInitializer implements Initializer
{
    #[Singleton]
    public function initialize(Container $container): TransactionManager
    {
        return new GenericTransactionManager($container->get(Connection::class));
    }
}
