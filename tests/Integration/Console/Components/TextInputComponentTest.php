<?php

declare(strict_types=1);

namespace Tests\Tempest\Integration\Console\Components;

use PHPUnit\Framework\Attributes\Test;
use Tempest\Console\Components\Interactive\TextInputComponent;
use Tempest\Console\Console;
use Tempest\Console\Terminal\Terminal;
use Tests\Tempest\Integration\FrameworkIntegrationTestCase;

/**
 * @internal
 */
final class TextInputComponentTest extends FrameworkIntegrationTestCase
{
    #[Test]
    public function test_basic_input(): void
    {
        $this->console->withoutPrompting()->call(function (Console $console): void {
            $terminal = new Terminal($console);
            $component = new TextInputComponent(label: 'Enter your name', placeholder: 'Jon Doe', multiline: false);

            $this->assertStringContainsString('Enter your name', $component->render($terminal));
            $this->assertStringContainsString('Jon Doe', $component->render($terminal));

            $component->input('a');
            $component->input('b');
            $component->input("\n");
            $component->input("\r\n");
            $component->input('c');

            $this->assertStringContainsString('abc', $component->render($terminal));
            $this->assertStringNotContainsString('Jon Doe', $component->render($terminal));

            $component->deletePreviousCharacter();

            $this->assertSame('ab', $component->enter());
        });
    }

    #[Test]
    public function test_multiline_input(): void
    {
        $this->console->withoutPrompting()->call(function (Console $console): void {
            $terminal = new Terminal($console);
            $component = new TextInputComponent(label: 'Enter your name', multiline: true);

            $this->assertStringContainsString('Enter your name', $component->render($terminal));

            $component->input('1');
            $component->enter();
            $component->input('2');
            $component->enter();
            $component->input('3');

            $this->assertStringContainsString('1', $component->render($terminal));
            $this->assertStringContainsString('2', $component->render($terminal));
            $this->assertStringContainsString('3', $component->render($terminal));

            $component->deletePreviousCharacter();

            $this->assertStringEqualsStringIgnoringLineEndings("1\n2\n", $component->altEnter());
        });
    }

    #[Test]
    public function test_multiline_input_with_scroll(): void
    {
        $this->console->withoutPrompting()->call(function (Console $console): void {
            $terminal = new Terminal($console);
            $component = new TextInputComponent(label: 'Enter your name', multiline: true);

            $this->assertStringContainsString('Enter your name', $component->render($terminal));

            $component->input('1');
            $component->enter();
            $component->input('2');
            $component->enter();
            $component->input('3');
            $component->enter();
            $component->input('4');
            $component->enter();
            $component->input('5');
            $component->enter();
            $component->input('6');

            $this->assertStringNotContainsString('1', $component->render($terminal));
            $this->assertStringNotContainsString('2', $component->render($terminal));
            $this->assertStringContainsString('3', $component->render($terminal));
            $this->assertStringContainsString('4', $component->render($terminal));
            $this->assertStringContainsString('5', $component->render($terminal));
            $this->assertStringContainsString('6', $component->render($terminal));

            $component->up();
            $component->up();
            $component->up();
            $component->up();
            $component->up();

            $this->assertStringContainsString('1', $component->render($terminal));
            $this->assertStringNotContainsString('6', $component->render($terminal));

            $this->assertStringEqualsStringIgnoringLineEndings("1\n2\n3\n4\n5\n6", $component->altEnter());
        });
    }

    #[Test]
    public function test_single_line_cannot_have_new_lines(): void
    {
        $this->console->withoutPrompting()->call(function (): void {
            $component = new TextInputComponent(label: 'Enter your name', placeholder: 'Jon Doe', multiline: false);

            $component->input('a');
            $component->input(PHP_EOL);

            $this->assertNull($component->altEnter());
            $this->assertStringEqualsStringIgnoringLineEndings('a', $component->enter());
        });
    }

    #[Test]
    public function test_multiline_may_have_new_lines(): void
    {
        $this->console->withoutPrompting()->call(function (): void {
            $component = new TextInputComponent(label: 'Enter your name', placeholder: 'Jon Doe', multiline: true);

            $component->input('a');
            $component->input(PHP_EOL);

            $this->assertNull($component->enter());
            $this->assertStringEqualsStringIgnoringLineEndings("a\n\n", $component->altEnter());
        });
    }

    #[Test]
    public function test_truncates_label(): void
    {
        $this->console->withoutPrompting()->call(function (Console $console): void {
            $terminal = new Terminal($console);
            $component = new TextInputComponent(label: str_repeat('a', 200), placeholder: 'Jon Doe');

            $terminal->width = 20;
            $this->assertStringContainsString('aaaaaaaaaaaaa…', $component->render($terminal));

            $terminal->width = 25;
            $this->assertStringContainsString('aaaaaaaaaaaaaaaaaa…', $component->render($terminal));
        });
    }
}
