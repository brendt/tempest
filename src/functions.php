<?php

declare(strict_types=1);

namespace Tempest {

    use Tempest\Commands\CommandBus;
    use Tempest\Container\Container;
    use Tempest\Container\GenericContainer;
    use Tempest\Events\EventBus;
    use Tempest\Http\GenericResponse;
    use Tempest\Http\Response;
    use Tempest\Http\Router;
    use Tempest\Http\Status;
    use Tempest\Mapper\ObjectMapper;
    use Tempest\Support\Reflection\Attributes;
    use Tempest\View\GenericView;
    use Tempest\View\View;
    use Tempest\Container\Exceptions\CannotInstantiateDependencyException;

    /**
     * @template TClassName
     * @param class-string<TClassName> $className
     * @return TClassName
     */
    function get(string $className): object
    {
        $container = GenericContainer::instance();

        return $container->get($className);
    }

    /**
     * @template TClassName
     * @param class-string<TClassName> $className
     * @param callable $definition
     *
     * @return Container
     * @throws CannotInstantiateDependencyException
     */
    function swap(string $className, callable $definition): object
    {
        $container = GenericContainer::instance();

        return $container->swap($className, $definition);
    }

    function path(string ...$parts): string
    {
        $path = implode('/', $parts);

        return str_replace(
            ['//', '\\', '\\\\'],
            ['/', '/', '/'],
            $path,
        );
    }

    function view(string $path): View
    {
        return new GenericView($path);
    }

    function response(string $body = '', Status $status = Status::OK): Response
    {
        return new GenericResponse($status, $body);
    }

    function uri(array|string $action, ...$params): string
    {
        $router = get(Router::class);

        return $router->toUri(
            $action,
            ...$params,
        );
    }

    function redirect(string|array $action, ...$params): Response
    {
        return response()->redirect(uri($action, ...$params));
    }

    /**
     * @template T of object
     * @param T|class-string<T> $objectOrClass
     * @return ObjectMapper<T>
     */
    function make(object|string $objectOrClass): ObjectMapper
    {
        $factory = get(ObjectMapper::class);

        return $factory->forClass($objectOrClass);
    }

    function map(mixed $data): ObjectMapper
    {
        $factory = get(ObjectMapper::class);

        return $factory->withData($data);
    }

    /**
     * @template T of object
     * @param class-string<T> $attributeName
     * @return \Tempest\Support\Reflection\Attributes<T>
     */
    function attribute(string $attributeName): Attributes
    {
        return Attributes::find($attributeName);
    }

    function command(object $command): void
    {
        $commandBus = get(CommandBus::class);

        $commandBus->dispatch($command);
    }

    function event(object $event): void
    {
        $eventBus = get(EventBus::class);

        $eventBus->dispatch($event);
    }

    function env(string $key, mixed $default = null): mixed
    {
        $value = getenv($key);

        if ($value === false) {
            return $default;
        }

        return match (strtolower($value)) {
            'true' => true,
            'false' => false,
            'null', '' => null,
            default => $value,
        };
    }
}
