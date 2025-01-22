<?php

declare(strict_types=1);

namespace Tests\Tempest\Integration;

use PHPUnit\Framework\TestCase;
use Tempest\Container\Container;
use Tempest\Core\AppConfig;
use Tempest\Core\Kernel;
use Tempest\Discovery\DiscoveryLocation;

abstract class IntegrationTestCase extends TestCase
{
    protected AppConfig $appConfig;

    protected Kernel $kernel;

    protected Container $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kernel = Kernel::boot(
            root: getcwd(),
            discoveryLocations: [
                new DiscoveryLocation(
                    'Tests\\Tempest\\Fixtures\\',
                    __DIR__ . '/../Fixtures/',
                ),
            ],
        );

        $this->container = ($this->kernel)->container;
    }
}
