<?php

declare(strict_types=1);

namespace Tests\Tempest\Integration\Console\Renderers;

use Tempest\Console\Components\Renderers\MessageRenderer;
use Tests\Tempest\Integration\FrameworkIntegrationTestCase;

/**
 * @internal
 */
final class MessageRendererTest extends FrameworkIntegrationTestCase
{
    public function test_render_message(): void
    {
        $renderer = new MessageRenderer('ERR', 'blue');
        $rendered = $renderer->render('Hello, World!');

        $this->assertStringContainsString('ERR', $rendered);
        $this->assertStringContainsString('fg-blue', $rendered);
        $this->assertStringContainsString('Hello, World!', $rendered);
    }
}
