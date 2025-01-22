<?php

declare(strict_types=1);

namespace Tests\Tempest\Integration\View;

use Tempest\Discovery\DiscoveryItems;
use Tempest\Discovery\DiscoveryLocation;
use Tempest\View\Components\Input;
use Tempest\View\Exceptions\DuplicateViewComponent;
use Tempest\View\ViewComponentDiscovery;
use Tests\Tempest\Integration\FrameworkIntegrationTestCase;

/**
 * @internal
 */
final class ViewComponentDiscoveryTest extends FrameworkIntegrationTestCase
{
    public function test_duplicates(): void
    {
        $discovery = $this->container->get(ViewComponentDiscovery::class);
        $discovery->setItems(new DiscoveryItems([]));

        try {
            $discovery->discoverPath(new DiscoveryLocation('', ''), __DIR__ . '/duplicateComponent.view.php');
            $discovery->apply();
        } catch (DuplicateViewComponent $duplicateViewComponent) {
            $this->assertStringContainsString(__DIR__ . '/duplicateComponent.view.php', $duplicateViewComponent->getMessage());
            $this->assertStringContainsString(Input::class, $duplicateViewComponent->getMessage());
            $this->assertStringContainsString('x-input', $duplicateViewComponent->getMessage());
        }
    }
}
