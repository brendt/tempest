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
final class AccessibleTest extends TestCase
{
    #[Test]
    public function from_name_method_with_backed_enum(): void {
        $this->assertSame(
            expected: SampleStatusBackedEnum::PUBLISH,
            actual  : SampleStatusBackedEnum::fromName('PUBLISH')
        );

        // It's case sensitive
        $this->expectException(\ValueError::class);
        
        SampleStatusBackedEnum::fromName('publish');
    }

    #[Test]
    public function from_name_method_with_pure_enum(): void {
        $this->assertSame(
            expected: SampleStatusPureEnum::PUBLISH,
            actual  : SampleStatusPureEnum::fromName('PUBLISH')
        );

        // It's case sensitive
        $this->expectException(\ValueError::class);
        
        SampleStatusPureEnum::fromName('publish');
    }

    #[Test]
    public function try_from_name_method_with_backed_enum(): void {
        $this->assertSame(
            expected: SampleStatusBackedEnum::PUBLISH,
            actual  : SampleStatusBackedEnum::tryFromName('PUBLISH')
        );

        $this->assertNull(
            SampleStatusBackedEnum::tryFromName('publish')
        );
    }

    #[Test]
    public function try_from_name_method_with_pure_enum(): void {
        $this->assertSame(
            expected: SampleStatusPureEnum::PUBLISH,
            actual  : SampleStatusPureEnum::tryFromName('PUBLISH')
        );

        // It's case sensitive
        $this->assertNull(
            SampleStatusPureEnum::tryFromName('publish')
        );
    }

    #[Test]
    public function from_method_with_backed_enum(): void {
        $this->assertSame(
            expected: SampleStatusBackedEnum::PUBLISH,
            actual  : SampleStatusBackedEnum::from('publish')
        );

        // It's case sensitive
        $this->expectException(\ValueError::class);
        
        SampleStatusBackedEnum::from('PUBLISH');
    }

    #[Test]
    public function from_method_with_pure_enum(): void {
        $this->assertSame(
            expected: SampleStatusPureEnum::PUBLISH,
            actual  : SampleStatusPureEnum::from('PUBLISH')
        );
    }

    #[Test]
    public function try_from_method_with_backed_enum(): void {
        $this->assertSame(
            expected: SampleStatusBackedEnum::PUBLISH,
            actual  : SampleStatusBackedEnum::tryFrom('publish')
        );

        // It's case sensitive
        $this->assertNull(
            SampleStatusBackedEnum::tryFrom('PUBLISH')
        );
    }

    #[Test]
    public function try_from_method_with_pure_enum(): void {
        $this->assertSame(
            expected: SampleStatusPureEnum::PUBLISH,
            actual  : SampleStatusPureEnum::tryFrom('PUBLISH')
        );
    }
}