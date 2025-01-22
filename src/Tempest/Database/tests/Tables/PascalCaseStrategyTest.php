<?php

declare(strict_types=1);

namespace Tempest\Database\Tests\Tables;

use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Tempest\Database\Migrations\Migration;
use Tempest\Database\Tables\PascalCaseStrategy;

/**
 * @internal
 */
final class PascalCaseStrategyTest extends TestCase
{
    #[TestWith([Migration::class, 'Migration'])]
    #[TestWith(['App\\Models\\PersonalAccessToken', 'PersonalAccessToken'])]
    public function test_strategy(string $actual, string $expected): void
    {
        $this->assertSame($expected, new PascalCaseStrategy()->getName($actual));
    }
}
