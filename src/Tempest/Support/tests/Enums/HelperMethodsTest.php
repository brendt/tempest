<?php

declare(strict_types=1);

namespace Tempest\Support\Tests;

use Tempest\Support\Tests\Fixtures\Enums\SampleStatusPureEnum;
use Tempest\Support\Tests\Fixtures\Enums\SampleStatusBackedEnum;
use Tempest\Support\Tests\Fixtures\Enums\EmptyEnum;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 */
final class HelperMethodsTest extends TestCase
{
    #[Test]
    public function names_method_backed_enum(): void {
        $this->assertSame(
            expected: SampleStatusBackedEnum::names(),
            actual: [
                'PUBLISH',
                'DRAFT',
                'TRASH'
            ]
        );
    }

    #[Test]
    public function names_method_pure_enum(): void {
        $this->assertSame(
            expected: SampleStatusPureEnum::names(),
            actual: [
                'PUBLISH',
                'DRAFT',
                'TRASH'
            ]
        );
    }

    #[Test]
    public function names_returns_empty_array_with_empty_enum(): void {
        $this->assertSame(
            expected: EmptyEnum::names(),
            actual: []
        );
    }

    #[Test]
    public function values_method_backed_enum(): void {
        $this->assertSame(
            expected: SampleStatusBackedEnum::values(),
            actual: [
                'publish',
                'draft',
                'trash'
            ]
        );
    }

    #[Test]
    public function values_method_pure_enum(): void {
        $this->assertSame(
            expected: SampleStatusPureEnum::values(),
            actual: [
                'PUBLISH',
                'DRAFT',
                'TRASH'
            ]
        );
    }

    #[Test]
    public function values_returns_empty_array_with_empty_enum(): void {
        $this->assertSame(
            expected: EmptyEnum::values(),
            actual: []
        );
    }
}