<?php

declare(strict_types=1);

namespace Tempest\Support\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Tempest\Support\Tests\Fixtures\Enums\SampleStatusBackedEnum;
use Tempest\Support\Tests\Fixtures\Enums\SampleStatusPureEnum;

/**
 * @internal
 */
final class ComparableTest extends TestCase
{
    #[Test]
    public function is_method_with_backed_enum(): void {
        $this->assertTrue(
            SampleStatusBackedEnum::PUBLISH->is(SampleStatusBackedEnum::PUBLISH)
        );

        $this->assertFalse(
            SampleStatusBackedEnum::PUBLISH->is(SampleStatusBackedEnum::DRAFT)
        );

        $this->assertFalse(
            SampleStatusBackedEnum::PUBLISH->is(SampleStatusPureEnum::PUBLISH)
        );
    }

    #[Test]
    public function is_method_with_pure_enum(): void {
        $this->assertTrue(
            SampleStatusPureEnum::PUBLISH->is(SampleStatusPureEnum::PUBLISH)
        );

        $this->assertFalse(
            SampleStatusPureEnum::PUBLISH->is(SampleStatusPureEnum::DRAFT)
        );

        $this->assertFalse(
            SampleStatusPureEnum::PUBLISH->is(SampleStatusBackedEnum::PUBLISH)
        );
    }

    #[Test]
    public function is_not_method_with_backed_enum(): void {
        $this->assertFalse(
            SampleStatusBackedEnum::PUBLISH->isNot(SampleStatusBackedEnum::PUBLISH)
        );

        $this->assertTrue(
            SampleStatusBackedEnum::PUBLISH->isNot(SampleStatusBackedEnum::DRAFT)
        );

        $this->assertTrue(
            SampleStatusBackedEnum::PUBLISH->isNot(SampleStatusPureEnum::PUBLISH)
        );
    }

    #[Test]
    public function is_not_method_with_pure_enum(): void {
        $this->assertFalse(
            SampleStatusPureEnum::PUBLISH->isNot(SampleStatusPureEnum::PUBLISH)
        );

        $this->assertTrue(
            SampleStatusPureEnum::PUBLISH->isNot(SampleStatusPureEnum::DRAFT)
        );

        $this->assertTrue(
            SampleStatusPureEnum::PUBLISH->isNot(SampleStatusBackedEnum::PUBLISH)
        );
    }
}