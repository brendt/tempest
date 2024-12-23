<?php

declare(strict_types=1);

namespace Tests\Tempest\Integration\Console\Components\Static;

use Tempest\Console\Console;
use Tests\Tempest\Integration\Console\Fixtures\TestStringEnum;
use Tests\Tempest\Integration\FrameworkIntegrationTestCase;

/**
 * @internal
 */
final class StaticMultipleChoiceComponentTest extends FrameworkIntegrationTestCase
{
    public function test_ask(): void
    {
        $this->console
            ->call(function (Console $console): void {
                $console->ask('test', ['a', 'b', 'c'], multiple: true);
            })
            ->submit('0,1')
            ->assertContains('You picked a and b;');
    }

    public function test_with_invalid_options(): void
    {
        $this->console
            ->call(function (Console $console): void {
                $console->ask('test', ['a', 'b', 'c'], multiple: true);
            })
            ->submit('0,4,c,2')
            ->assertContains('You picked a and c;');
    }

    public function test_confirm(): void
    {
        $this->console
            ->call(function (Console $console): void {
                $answer = $console->ask('test', ['a', 'b', 'c'], multiple: true);

                $console->writeln(json_encode($answer));
            })
            ->submit('0')
            ->assertContains('You picked a;')
            ->submit('no')
            ->assertContains('- [0] a')
            ->submit('0,1')
            ->assertContains('You picked a and b;')
            ->submit('yes')
            ->assertContains('["a","b"]');
    }

    public function test_supports_defaults(): void
    {
        $this->console
            ->call(function (Console $console): void {
                $answer = $console->ask(
                    question: 'test',
                    options: ['foo', 'bar'],
                    default: ['foo'],
                    multiple: true,
                );

                $console->writeln(json_encode($answer));
            })
            ->submit()
            ->submit()
            ->assertContains(json_encode(['foo']));
    }

    public function test_supports_enum(): void
    {
        $this->console
            ->call(function (Console $console): void {
                $answer = $console->ask(
                    question: 'test',
                    options: TestStringEnum::cases(),
                    multiple: true,
                );

                $console->writeln(json_encode($answer));
            })
            ->assertSee('[0] a')
            ->assertSee('[1] b')
            ->assertSee('[2] c')
            ->submit('a,c')
            ->submit()
            ->assertContains(json_encode(['a', 'c']));
    }

    public function test_supports_enum_with_default(): void
    {
        $this->console
            ->call(function (Console $console): void {
                $answer = $console->ask(
                    question: 'test',
                    options: TestStringEnum::cases(),
                    default: TestStringEnum::A,
                    multiple: true,
                );

                $console->writeln(json_encode($answer));
            })
            ->submit()
            ->submit()
            ->assertContains(json_encode(['a']));
    }
}
