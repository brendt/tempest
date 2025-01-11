<?php

declare(strict_types=1);

namespace Tempest\Database;

use Tempest\Container\Container;
use Tempest\Container\Initializer;
use Tempest\Container\Singleton;
use Tempest\Database\Transactions\TransactionManager;

final readonly class DatabaseInitializer implements Initializer
{
    #[Singleton]
    public function initialize(Container $container): Database
    {
        return new GenericDatabase(
            $container->get(Connection::class),
            $container->get(TransactionManager::class),
        );
    }
}
