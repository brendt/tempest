<?php

declare(strict_types=1);

namespace Tempest\Database;

use Tempest\Container\Container;
use Tempest\Container\Initializer;
use Tempest\Container\Singleton;

/**
 * Reuses the same connection instance based on a static variable instead of the container.
 *
 * Used in testing where each test can have its own container instance.
 */
final class CachedConnectionInitializer implements Initializer
{
    private static Connection|null $instance = null;

    public function __construct(private readonly ConnectionInitializer $initializer)
    {
    }

    #[Singleton]
    public function initialize(Container $container): Connection
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        self::$instance = $this->initializer->initialize($container);

        return self::$instance;
    }
}
