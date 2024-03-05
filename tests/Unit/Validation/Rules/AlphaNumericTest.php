<?php

declare(strict_types=1);

namespace Tests\Tempest\Unit\Validation\Rules;

use PHPUnit\Framework\TestCase;
use Tempest\Validation\Rules\AlphaNumeric;

/**
 * @internal
 * @small
 */
class AlphaNumericTest extends TestCase
{
    public function test_alphanumeric()
    {
        $rule = new AlphaNumeric();

        $this->assertSame('Value should only contain alphanumeric characters', $rule->message());
        $this->assertFalse($rule->isValid('string_123'));
        $this->assertTrue($rule->isValid('string123'));
        $this->assertTrue($rule->isValid('STRING123'));
    }
}
