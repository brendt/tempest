<?php

declare(strict_types=1);

namespace Tempest\Database\Connections;

use Tempest\Container\Container;
use Tempest\Container\Initializer;
use Tempest\Container\Singleton;
use Tempest\Database\DatabaseConfig;

final class DatabaseConnectionInitializer implements Initializer
{
    #[Singleton]
    public function initialize(Container $container): DatabaseConnection
    {
        return $container->get(DatabaseConfig::class)->connection();
    }
}
