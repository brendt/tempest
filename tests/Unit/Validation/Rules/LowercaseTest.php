<?php

declare(strict_types=1);

namespace Tests\Tempest\Unit\Validation\Rules;

use Tempest\Validation\Rules\Lowercase;
use Tests\Tempest\TestCase;

class LowercaseTest extends TestCase
{
    public function test_lowercase()
    {
        $rule = new Lowercase();

        $this->assertSame('Value should be a lowercase string', $rule->message());

        $this->assertTrue($rule->isValid('abc'));
        $this->assertTrue($rule->isValid('àbç'));
        $this->assertFalse($rule->isValid('ABC'));
        $this->assertFalse($rule->isValid('ÀBÇ'));
        $this->assertFalse($rule->isValid('AbC'));
    }
}
