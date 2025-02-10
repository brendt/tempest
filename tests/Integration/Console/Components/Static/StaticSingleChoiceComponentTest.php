<?php

declare(strict_types=1);

namespace Tests\Tempest\Integration\Console\Components\Static;

use Tempest\Console\Console;
use Tempest\Console\Key;
use Tests\Tempest\Integration\Console\Fixtures\TestStringEnum;
use Tests\Tempest\Integration\FrameworkIntegrationTestCase;

/**
 * @internal
 */
final class StaticSingleChoiceComponentTest extends FrameworkIntegrationTestCase
{
    public function test_with_options(): void
    {
        $this->console
            ->call(function (Console $console): void {
                $answer = $console->ask('test', ['a', 'b']);

                $console->writeln("picked {$answer}");
            })
            ->input(Key::ENTER)
            ->assertDoesNotContain('picked a')
            ->submit('a')
            ->assertContains('picked a');
    }

    public function test_with_default_option(): void
    {
        $this->console
            ->call(function (Console $console): void {
                $answer = $console->ask('test', ['a', 'b'], default: 'b');

                $console->writeln("picked {$answer}");
            })
            ->input(Key::ENTER)
            ->assertContains('picked b');
    }

    public function test_with_default_option_without_prompting(): void
    {
        $this->console
            ->withoutPrompting()
            ->call(function (Console $console): void {
                $answer = $console->ask('test', ['a', 'b'], default: 'b');

                $console->writeln("picked {$answer}");
            })
            ->assertContains('picked b');
    }

    public function test_assoc_submit_key(): void
    {
        $this->console
            ->call(function (Console $console): void {
                $answer = $console->ask('test', ['a' => 'A', 'b' => 'B']);

                $console->writeln("picked {$answer}");
            })
            ->submit(1)
            ->assertContains('picked B');
    }

    public function test_assoc_submit_value(): void
    {
        $this->console
            ->call(function (Console $console): void {
                $answer = $console->ask('test', ['a' => 'A', 'b' => 'B']);

                $console->writeln("picked {$answer}");
            })
            ->submit('B')
            ->assertContains('picked B');
    }

    public function test_enum_submit_value(): void
    {
        $this->console
            ->call(function (Console $console): void {
                $answer = $console->ask('test', options: TestStringEnum::cases());

                $console->writeln("picked {$answer->value}");
            })
            ->submit('b')
            ->assertContains('picked b');
    }

    public function test_enum_submit_index(): void
    {
        $this->console
            ->call(function (Console $console): void {
                $answer = $console->ask('test', options: TestStringEnum::cases());

                $console->writeln("picked {$answer->value}");
            })
            ->submit(1)
            ->assertContains('picked b');
    }

    public function test_enum_default_value(): void
    {
        $this->console
            ->call(function (Console $console): void {
                $answer = $console->ask('test', options: TestStringEnum::cases(), default: TestStringEnum::B);

                $console->writeln("picked {$answer->value}");
            })
            ->submit()
            ->assertContains('picked b');
    }
}
