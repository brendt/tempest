<?php

declare(strict_types=1);

namespace Tests\Tempest\Integration\Framework\Commands;

use Tests\Tempest\Fixtures\Modules\Posts\PostController;
use Tests\Tempest\Integration\FrameworkIntegrationTestCase;

/**
 * @internal
 */
final class RoutesCommandTest extends FrameworkIntegrationTestCase
{
    public function test_displays_uri_and_controller(): void
    {
        $this->console
            ->call('routes')
            ->assertContains('/create-post')
            ->assertContains(PostController::class);
    }

    public function test_outputs_as_json(): void
    {
        $this->console
            ->call('routes', ['--json'])
            ->assertJson();
    }
}
