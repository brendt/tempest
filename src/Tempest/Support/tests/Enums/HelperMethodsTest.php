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
}