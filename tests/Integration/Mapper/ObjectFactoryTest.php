<?php

declare(strict_types=1);

namespace Tests\Tempest\Integration\Mapper;

use Tempest\Mapper\Exceptions\CannotMapDataException;
use Tempest\Mapper\Mappers\ArrayToJsonMapper;
use Tempest\Mapper\Mappers\MixedToObjectMapper;
use Tempest\Mapper\Mappers\ObjectToArrayMapper;
use Tempest\Mapper\ObjectFactory;
use Tests\Tempest\Integration\FrameworkIntegrationTestCase;
use Tests\Tempest\Integration\Mapper\Fixtures\ObjectA;
use function Tempest\make;
use function Tempest\map;

/**
 * @internal
 */
final class ObjectFactoryTest extends FrameworkIntegrationTestCase
{
    public function test_single_object(): void
    {
        $factory = $this->container->get(ObjectFactory::class);

        $object = $factory->map(
            ['a' => 'a', 'b' => 'b'],
            ObjectA::class,
        );

        $this->assertEquals('a', $object->a);
        $this->assertEquals('b', $object->b);
    }

    public function test_collection(): void
    {
        $factory = $this->container->get(ObjectFactory::class);

        $objects = $factory->collection()->map(
            [['a' => 'a', 'b' => 'b'], ['a' => 'aa', 'b' => 'bb']],
            ObjectA::class,
        );

        $this->assertCount(2, $objects);

        $this->assertEquals('a', $objects[0]->a);
        $this->assertEquals('b', $objects[0]->b);

        $this->assertEquals('aa', $objects[1]->a);
        $this->assertEquals('bb', $objects[1]->b);
    }

    public function test_make_function(): void
    {
        $object = make(ObjectA::class)->from(['a' => 'a', 'b' => 'b']);

        $this->assertEquals('a', $object->a);
        $this->assertEquals('b', $object->b);
    }

    public function test_map_function(): void
    {
        $object = map(['a' => 'a', 'b' => 'b'])->to(ObjectA::class);

        $this->assertEquals('a', $object->a);
        $this->assertEquals('b', $object->b);
    }

    public function test_cannot_map_exception(): void
    {
        $this->expectException(CannotMapDataException::class);

        /** @phpstan-ignore-next-line  */
        map(['a' => 'a', 'b' => 'b'])->to('unknown');
    }

    public function test_map_with(): void
    {
        $result = map(['a' => 'a', 'b' => 'b'])->with(
            fn (MixedToObjectMapper $mapper, mixed $from) => $mapper->map($from, ObjectA::class),
            ObjectToArrayMapper::class,
            ArrayToJsonMapper::class,
        );

        $this->assertSame('{"a":"a","b":"b"}', $result);
    }
}
