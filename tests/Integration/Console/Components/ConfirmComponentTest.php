<?php

declare(strict_types=1);

namespace Tests\Tempest\Integration\Console\Components;

use Tempest\Console\Components\Interactive\ConfirmComponent;
use Tempest\Console\Console;
use Tempest\Console\Terminal\Terminal;
use Tests\Tempest\Integration\FrameworkIntegrationTestCase;

/**
 * @internal
 */
final class ConfirmComponentTest extends FrameworkIntegrationTestCase
{
    public function test_confirm_component(): void
    {
        $this->console->withoutPrompting()->call(function (Console $console): void {
            $terminal = new Terminal($console);
            $component = new ConfirmComponent('Label', yes: 'Yes!', no: 'No!');

            $this->assertStringContainsString('Yes!', $component->render($terminal));
            $this->assertStringContainsString('No!', $component->render($terminal));

            $component->toggle();

            $this->assertStringContainsString('Yes!', $component->render($terminal));
            $this->assertStringContainsString('No!', $component->render($terminal));

            $this->assertTrue($component->enter());
        });
    }

    public function test_confirm_component_shortcuts(): void
    {
        $this->console->withoutPrompting()->call(function (): void {
            $component = new ConfirmComponent('Label');

            $component->input('n');
            $this->assertFalse($component->enter());

            $component->input('y');
            $this->assertTrue($component->enter());
        });
    }

    public function test_confirm_component_default(): void
    {
        // false by default
        $this->console->withoutPrompting()->call(function (): void {
            $component = new ConfirmComponent('Label');
            $this->assertFalse($component->enter());
        });

        $this->console->withoutPrompting()->call(function (): void {
            $component = new ConfirmComponent('Label', default: true);
            $this->assertTrue($component->enter());
        });

        $this->console->withoutPrompting()->call(function (): void {
            $component = new ConfirmComponent('Label', default: false);
            $this->assertFalse($component->enter());
        });
    }
}
