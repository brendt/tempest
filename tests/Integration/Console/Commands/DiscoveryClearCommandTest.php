<?php

declare(strict_types=1);

namespace Tests\Tempest\Integration\Console\Commands;

use Tempest\AppConfig;
use Tempest\Testing\IntegrationTest;
use Tests\Tempest\Integration\Console\Fixtures\MyDiscovery;

/**
 * @internal
 * @small
 */
class DiscoveryClearCommandTest extends IntegrationTest
{
    public function test_it_clears_discovery_cache()
    {
        $appConfig = $this->container->get(AppConfig::class);

        MyDiscovery::$cacheCleared = false;

        $appConfig->discoveryClasses = [MyDiscovery::class];

        $this->console->call('discovery:clear');

        $this->assertTrue(MyDiscovery::$cacheCleared);
    }
}
