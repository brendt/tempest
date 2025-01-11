<?php

declare(strict_types=1);

namespace Tempest\Database;

use Tempest\Container\Container;
use Tempest\Container\Initializer;
use Tempest\Database\Connections\DatabaseConnection;

final readonly class DatabaseDialectInitializer implements Initializer
{
    public function initialize(Container $container): DatabaseDialect
    {
        return $container->get(DatabaseConnection::class)->dialect();
    }
}
