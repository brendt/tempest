<?php

declare(strict_types=1);

namespace Tempest {
    use Tempest\Mapper\ObjectFactory;

    /**
     * Creates a factory which allows instantiating `$objectOrClass` with the data specified by the {@see \Tempest\Mapper\ObjectFactory::from()} method.
     *
     * ### Example
     * ```php
     * make(Author::class)->from([
     *   'first_name' => 'Jon',
     *   'last_name' => 'Doe',
     * ])
     * ```
     *
     * @template T of object
     * @param T|class-string<T> $objectOrClass
     * @return ObjectFactory<T>
     */
    function make(object|string $objectOrClass): ObjectFactory
    {
        $factory = get(ObjectFactory::class);

        return $factory->forClass($objectOrClass);
    }

    /**
     * Creates a factory which allows instantiating the object or class specified by {@see \Tempest\Mapper\ObjectFactory::to()} the given `$data`.
     *
     * ### Example
     * ```php
     * map([
     *   'first_name' => 'Jon',
     *   'last_name' => 'Doe',
     * ])->to($author);
     * ```
     */
    function map(mixed $data): ObjectFactory
    {
        $factory = get(ObjectFactory::class);

        return $factory->withData($data);
    }
}
