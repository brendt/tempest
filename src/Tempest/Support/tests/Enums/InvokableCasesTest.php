<?php

declare(strict_types=1);

namespace Tempest\Support\Tests;

use Tempest\Support\Tests\Fixtures\Enums\SampleStatusBackedEnum;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Tempest\Support\Tests\Fixtures\Enums\SampleStatusPureEnum;

/**
 * @internal
 */
final class InvokableCasesTest extends TestCase
{
    #[Test]
    public function invokable_case_backed_enum(): void {
        $case = SampleStatusBackedEnum::PUBLISH;
        
        $this->assertSame(
            expected: SampleStatusBackedEnum::PUBLISH->value,
            actual: $case()
        );
    }

    #[Test]
    public function invokable_case_pure_enum(): void {
        $case = SampleStatusPureEnum::PUBLISH;
        
        $this->assertSame(
            expected: SampleStatusPureEnum::PUBLISH->name,
            actual: $case()
        );
    }
}