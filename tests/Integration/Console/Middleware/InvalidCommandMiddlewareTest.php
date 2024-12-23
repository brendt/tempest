<?php

declare(strict_types=1);

namespace Tests\Tempest\Integration\Console\Middleware;

use Tests\Tempest\Integration\Console\Fixtures\ComplexCommand;
use Tests\Tempest\Integration\Console\Fixtures\IntEnumCommand;
use Tests\Tempest\Integration\Console\Fixtures\StringEnumCommand;
use Tests\Tempest\Integration\FrameworkIntegrationTestCase;

/**
 * @internal
 */
final class InvalidCommandMiddlewareTest extends FrameworkIntegrationTestCase
{
    public function test_provide_missing_input(): void
    {
        $this->console
            ->call(ComplexCommand::class)
            ->assertContains('COMPLEX')
            ->submit('a')
            ->submit('b')
            ->submit('c')
            ->assertContains('abc');
    }

    public function test_with_string_enum(): void
    {
        $this->console
            ->call(StringEnumCommand::class)
            ->assertContains('A')
            ->assertContains('B')
            ->assertContains('C')
            ->input(1)
            ->assertContains('b');
    }

    public function test_with_int_enum(): void
    {
        $this->console
            ->call(IntEnumCommand::class)
            ->assertContains('A')
            ->assertContains('B')
            ->assertContains('C')
            ->input(1)
            ->assertContains('B');
    }
}
