<?php

declare(strict_types=1);

namespace Tempest\Database\Tests\Tables;

use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Tempest\Container\GenericContainer;
use Tempest\Database\Migrations\Migration;
use Tempest\Database\Tables\PluralizedSnakeCaseStrategy;
use Tempest\Support\Pluralizer\PluralizerInitializer;

/**
 * @internal
 */
final class PluralizedSnakeCaseStrategyTest extends TestCase
{
    #[TestWith([Migration::class, 'migrations'])]
    #[TestWith(['App\\Models\\PersonalAccessToken', 'personal_access_tokens'])]
    #[TestWith(['App\\Models\\Aircraft', 'aircraft'])] // does not take a "s" in plural form
    public function test_strategy(string $actual, string $expected): void
    {
        $container = new GenericContainer();

        $container->addInitializer(PluralizerInitializer::class);

        GenericContainer::setInstance($container);

        $this->assertSame($expected, new PluralizedSnakeCaseStrategy()->getName($actual));
    }
}
