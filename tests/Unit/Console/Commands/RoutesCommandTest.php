<?php

declare(strict_types=1);

namespace Tests\Tempest\Unit\Console\Commands;

use App\Modules\Posts\PostController;
use Tests\Tempest\TestCase;

class RoutesCommandTest extends TestCase
{
    /** @test */
    public function test_migrate_command()
    {
        $output = $this->console('routes')->asText();

        $this->assertStringContainsString('/create-post', $output);
        $this->assertStringContainsString(PostController::class, $output);
    }
}
