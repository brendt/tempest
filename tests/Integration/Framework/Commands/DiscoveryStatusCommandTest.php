<?php

declare(strict_types=1);

namespace Tests\Tempest\Integration\Framework\Commands;

use Tests\Tempest\Integration\FrameworkIntegrationTestCase;
use function Tempest\Support\str;

/**
 * @internal
 */
final class DiscoveryStatusCommandTest extends FrameworkIntegrationTestCase
{
    public function test_discovery_status_command(): void
    {
        $output = $this->console->call('discovery:status -cl');

        foreach ($this->kernel->discoveryClasses as $discoveryClass) {
            $output->assertContains(basename(str_replace('\\', '/', $discoveryClass)));
        }

        foreach ($this->kernel->discoveryLocations as $discoveryLocation) {
            $output->assertContains(str(realpath($discoveryLocation->path))->afterLast(['src/', 'vendor/', 'tests/'])->toString());
        }
    }
}
