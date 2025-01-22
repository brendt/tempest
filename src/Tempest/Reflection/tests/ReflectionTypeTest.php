<?php

declare(strict_types=1);

namespace Tempest\Reflection\Tests;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tempest\Database\DatabaseModel;
use Tempest\Reflection\Tests\Fixtures\A;
use Tempest\Reflection\Tests\Fixtures\B;
use Tempest\Reflection\Tests\Fixtures\C;
use Tempest\Reflection\TypeReflector;

/**
 * @internal
 */
final class ReflectionTypeTest extends TestCase
{
    #[DataProvider('data')]
    public function test_accepts(string $type, mixed $input, bool $expected): void
    {
        $this->assertSame(
            expected: $expected,
            actual: new TypeReflector($type)->accepts($input),
        );
    }

    public static function data(): Generator
    {
        yield ['string', 'test', true];
        yield ['string', [], false];
        yield ['string', 1, false];
        yield ['string|int', 1, true];
        // TODO: We will have to add a comparable test to this.
        //        yield [DatabaseModel::class, new A(new B(new C('test'))), true];
        yield [self::class, new A(new B(new C('test'))), false];
        yield ['string', null, false];
        yield ['?string', null, true];
    }

    public function test_as_class(): void
    {
        $this->assertSame(
            expected: A::class,
            actual: new TypeReflector(A::class)->asClass()->getName(),
        );

        $this->assertSame(
            expected: A::class,
            actual: new TypeReflector('?Tempest\Reflection\Tests\Fixtures\A')->asClass()->getName(),
        );
    }
}
