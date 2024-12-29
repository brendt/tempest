<?php

declare(strict_types=1);

namespace Tempest\Database;

use Tempest\Container\Container;
use Tempest\Container\Initializer;
use Tempest\Container\Singleton;
use Tempest\Core\AppConfig;

final class ConnectionInitializer implements Initializer
{
    private static Connection|null $instance = null;

    #[Singleton]
    public function initialize(Container $container): Connection
    {
        // Reuse same connection instance in unit tests
        if (self::$instance !== null && $container->get(AppConfig::class)->environment->isTesting()) {
            return self::$instance;
        }

        $databaseConfig = $container->get(DatabaseConfig::class);

        $connection = new PDOConnection($databaseConfig->connection());
        $connection->connect();

        self::$instance = $connection;

        return $connection;
    }
}
