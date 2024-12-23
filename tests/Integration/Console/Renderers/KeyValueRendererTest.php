<?php

declare(strict_types=1);

namespace Tests\Tempest\Integration\Console\Renderers;

use Tempest\Console\Components\Renderers\KeyValueRenderer;
use Tests\Tempest\Integration\FrameworkIntegrationTestCase;

/**
 * @internal
 */
final class KeyValueRendererTest extends FrameworkIntegrationTestCase
{
    public function test_render_line(): void
    {
        $renderer = new KeyValueRenderer();
        $rendered = $renderer->render('Foo', 'bar');

        $this->assertSame('Foo <style="fg-gray dim">.....................................................................................................................</style> bar', $rendered);
    }

    public function test_render_total_width_smaller_than_text(): void
    {
        $renderer = new KeyValueRenderer();
        $rendered = $renderer->render('Some long text', 'Some long text', maximumWidth: 0);

        $this->assertSame('Some long text <style="fg-gray dim">...</style> Some long text', $rendered);
    }
}
