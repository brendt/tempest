<?php

declare(strict_types=1);

namespace Tempest\Support\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Tempest\Support\Tests\Fixtures\Enums\SampleIntegerBackedEnum;
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

    #[Test]
    public function in_method_with_backed_enum(): void {
        $this->assertTrue(
            SampleStatusBackedEnum::PUBLISH->in([SampleStatusBackedEnum::PUBLISH, SampleStatusBackedEnum::DRAFT])
        );

        $this->assertFalse(
            SampleStatusBackedEnum::PUBLISH->in([SampleStatusBackedEnum::DRAFT, SampleStatusBackedEnum::TRASH])
        );

        $this->assertFalse(
            SampleStatusBackedEnum::PUBLISH->in([SampleStatusPureEnum::PUBLISH, SampleStatusPureEnum::DRAFT])
        );
    }

    #[Test]
    public function in_method_accepts_iterator(): void {
        $this->assertTrue(
            SampleStatusBackedEnum::PUBLISH->in(
                new \ArrayIterator([SampleStatusBackedEnum::PUBLISH, SampleStatusBackedEnum::DRAFT])
            )
        );
    }

    #[Test]
    public function in_method_returns_false_with_empty_array(): void {
        $this->assertFalse(
            SampleStatusBackedEnum::PUBLISH->in([])
        );
    }

    #[Test]
    public function in_method_with_pure_enum(): void {
        $this->assertTrue(
            SampleStatusPureEnum::PUBLISH->in([SampleStatusPureEnum::PUBLISH, SampleStatusPureEnum::DRAFT])
        );

        $this->assertFalse(
            SampleStatusPureEnum::PUBLISH->in([SampleStatusPureEnum::DRAFT, SampleStatusPureEnum::TRASH])
        );

        $this->assertFalse(
            SampleStatusPureEnum::PUBLISH->in([SampleStatusBackedEnum::PUBLISH, SampleStatusBackedEnum::DRAFT])
        );
    }

    #[Test]
    public function not_in_method_with_backed_enum(): void {
        $this->assertFalse(
            SampleStatusBackedEnum::PUBLISH->notIn([SampleStatusBackedEnum::PUBLISH, SampleStatusBackedEnum::DRAFT])
        );

        $this->assertTrue(
            SampleStatusBackedEnum::PUBLISH->notIn([SampleStatusBackedEnum::DRAFT, SampleStatusBackedEnum::TRASH])
        );

        $this->assertTrue(
            SampleStatusBackedEnum::PUBLISH->notIn([SampleStatusPureEnum::PUBLISH, SampleStatusPureEnum::DRAFT])
        );
    }

    #[Test]
    public function not_in_method_with_pure_enum(): void {
        $this->assertFalse(
            SampleStatusPureEnum::PUBLISH->notIn([SampleStatusPureEnum::PUBLISH, SampleStatusPureEnum::DRAFT])
        );

        $this->assertTrue(
            SampleStatusPureEnum::PUBLISH->notIn([SampleStatusPureEnum::DRAFT, SampleStatusPureEnum::TRASH])
        );

        $this->assertTrue(
            SampleStatusPureEnum::PUBLISH->notIn([SampleStatusBackedEnum::PUBLISH, SampleStatusBackedEnum::DRAFT])
        );
    }

    #[Test]
    public function not_in_method_accepts_iterator(): void {
        $this->assertTrue(
            SampleStatusBackedEnum::PUBLISH->notIn(
                new \ArrayIterator([SampleStatusBackedEnum::DRAFT, SampleStatusBackedEnum::TRASH])
            )
        );
    }

    #[Test]
    public function not_in_method_returns_true_with_empty_array(): void {
        $this->assertTrue(
            SampleStatusBackedEnum::PUBLISH->notIn([])
        );
    }

    #[Test]
    public function has_method_with_backed_enum(): void {
        $this->assertTrue(
            SampleStatusBackedEnum::has('PUBLISH')
        );

        $this->assertFalse(
            SampleStatusBackedEnum::has('REVISION')
        );

        // Not include value
        $this->assertFalse(
            SampleStatusBackedEnum::has('draft')
        );

        // Case sensitive
        $this->assertFalse(
            SampleStatusBackedEnum::has('Publish')
        );
    }

    #[Test]
    public function has_method_with_pure_enum(): void {
        $this->assertTrue(
            SampleStatusPureEnum::has('PUBLISH')
        );

        $this->assertFalse(
            SampleStatusPureEnum::has('NOT_FOUND')
        );

        // Case sensitive
        $this->assertFalse(
            SampleStatusPureEnum::has('Publish')
        );
    }

    #[Test]
    public function has_not_method_with_backed_enum(): void {
        $this->assertFalse(
            SampleStatusBackedEnum::hasNot('PUBLISH')
        );

        $this->assertTrue(
            SampleStatusBackedEnum::hasNot('REVISION')
        );

        // Not include value
        $this->assertTrue(
            SampleStatusBackedEnum::hasNot('draft')
        );

        // Case sensitive
        $this->assertTrue(
            SampleStatusBackedEnum::hasNot('Publish')
        );
    }

    #[Test]
    public function has_not_method_with_pure_enum(): void {
        $this->assertFalse(
            SampleStatusPureEnum::hasNot('PUBLISH')
        );

        $this->assertTrue(
            SampleStatusPureEnum::hasNot('NOT_FOUND')
        );

        // Case sensitive
        $this->assertTrue(
            SampleStatusPureEnum::hasNot('Publish')
        );
    }

    #[Test]
    public function has_value_method_with_backed_enum(): void {
        $this->assertTrue(
            SampleStatusBackedEnum::hasValue('publish')
        );

        $this->assertFalse(
            SampleStatusBackedEnum::hasValue('REVISION')
        );

        // Not include name
        $this->assertFalse(
            SampleStatusBackedEnum::hasValue('DRAFT')
        );

        // Case sensitive
        $this->assertFalse(
            SampleStatusBackedEnum::hasValue('Publish')
        );

        // Integer values
        $this->assertTrue(
            SampleIntegerBackedEnum::hasValue(404)
        );
    }

    #[Test]
    public function has_value_method_with_pure_enum(): void {
        $this->assertTrue(
            SampleStatusPureEnum::hasValue('PUBLISH')
        );

        $this->assertFalse(
            SampleStatusPureEnum::hasValue('NOT_FOUND')
        );

        // Case sensitive
        $this->assertFalse(
            SampleStatusPureEnum::hasValue('Publish')
        );
    }

    #[Test]
    public function has_not_value_method_with_backed_enum(): void {
        $this->assertFalse(
            SampleStatusBackedEnum::hasNotValue('publish')
        );

        $this->assertTrue(
            SampleStatusBackedEnum::hasNotValue('REVISION')
        );

        // Not include name
        $this->assertTrue(
            SampleStatusBackedEnum::hasNotValue('DRAFT')
        );

        // Case sensitive
        $this->assertTrue(
            SampleStatusBackedEnum::hasNotValue('Publish')
        );

        // Integer values
        $this->assertFalse(
            SampleIntegerBackedEnum::hasNotValue(404)
        );
    }

    #[Test]
    public function has_not_value_method_with_pure_enum(): void {
        $this->assertFalse(
            SampleStatusPureEnum::hasNotValue('PUBLISH')
        );

        $this->assertTrue(
            SampleStatusPureEnum::hasNotValue('NOT_FOUND')
        );

        // Case sensitive
        $this->assertTrue(
            SampleStatusPureEnum::hasNotValue('Publish')
        );
    }
}