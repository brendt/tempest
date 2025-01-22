<?php

declare(strict_types=1);

namespace Tests\Tempest\Integration\Core;

use PHPUnit\Framework\TestCase;
use Tempest\Container\Container;
use Tempest\Core\Kernel;
use Tempest\Discovery\DiscoveryLocation;
use Tests\Tempest\Fixtures\TestDependency;

/**
 * @internal
 */
final class KernelTest extends TestCase
{
    public function test_discovery_boot(): void
    {
        // TODO: Move this
        $kernel = Kernel::boot(
            root: getcwd(),
            discoveryLocations: [
                new DiscoveryLocation('Tests\\Tempest\\Fixtures\\', getcwd() . '/tests/Fixtures/'),
            ],
        );

        $this->assertInstanceOf(Container::class, $kernel->container);

        $this->assertNotEmpty($kernel->discoveryClasses);

        $test = $kernel->container->get(TestDependency::class);

        $this->assertInstanceOf(TestDependency::class, $test);
        $this->assertSame('test', $test->input);
    }

    public function test_kernel_start(): void
    {
        Kernel::boot(
            root: getcwd(),
            discoveryLocations: [],
        );

        $this->assertTrue(defined('TEMPEST_START'));
    }
}
