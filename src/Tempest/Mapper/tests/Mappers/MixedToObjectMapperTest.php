<?php

declare(strict_types=1);

namespace Tempest\Mapper\Tests\Mappers;

use PHPUnit\Framework\TestCase;
use Tempest\Mapper\Casters\CasterFactory;
use Tempest\Mapper\Mappers\MixedToObjectMapper;
use Tempest\Mapper\Tests\Support\StringCastValue;
use Tempest\Mapper\Tests\Support\StringValue;

/**
 * @internal
 */
final class MixedToObjectMapperTest extends TestCase
{
    private MixedToObjectMapper $subject;

    protected function setUp(): void
    {
        $this->subject = new MixedToObjectMapper(new CasterFactory());
    }

    public function test_map_array_to_object(): void
    {
        $value = ['value' => 'Tempest'];

        /** @var StringValue $object */
        $object = $this->subject->map($value, StringValue::class);

        $this->assertEquals('Tempest', $object->getValue());
    }

    public function test_map_string_to_object_with_automatic_cast(): void
    {
        $value = 'Tempest';

        /** @var StringCastValue $object */
        $object = $this->subject->map($value, StringCastValue::class);

        $this->assertEquals('Tempest', $object->getValue());
    }
}
