<?php

declare(strict_types=1);

namespace Tempest\Support\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tempest\Support\Tests\Fixtures\Enums\SampleStatusBackedEnum;
use Tempest\Support\Tests\Fixtures\Enums\SampleStatusPureEnum;

/**
 * @internal
 */
final class InvokableCasesTest extends TestCase
{
    #[Test]
    public function invokable_case_backed_enum(): void
    {
        $case = SampleStatusBackedEnum::PUBLISH;

        $this->assertSame(
            expected: SampleStatusBackedEnum::PUBLISH->value,
            actual: $case(),
        );
    }

    #[Test]
    public function invokable_case_pure_enum(): void
    {
        $case = SampleStatusPureEnum::PUBLISH;

        $this->assertSame(
            expected: SampleStatusPureEnum::PUBLISH->name,
            actual: $case(),
        );
    }

    #[Test]
    public function static_call_backed_enum(): void
    {
        $this->assertSame(
            expected: SampleStatusBackedEnum::PUBLISH->value,
            actual: SampleStatusBackedEnum::PUBLISH(),
        );
    }

    #[Test]
    public function static_call_pure_enum(): void
    {
        $this->assertSame(
            expected: SampleStatusPureEnum::PUBLISH->name,
            actual: SampleStatusPureEnum::PUBLISH(),
        );
    }

    #[Test]
    public function static_call_throw_exception_on_invalid_case(): void
    {
        $this->expectException(InvalidArgumentException::class);

        SampleStatusBackedEnum::UNKNOWN();
    }
}
