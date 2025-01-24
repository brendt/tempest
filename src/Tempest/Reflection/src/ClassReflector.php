<?php

declare(strict_types=1);

namespace Tempest\Reflection;

use Generator;
use ReflectionClass as PHPReflectionClass;
use ReflectionMethod as PHPReflectionMethod;
use ReflectionProperty as PHPReflectionProperty;

/**
 * @template TClassName of object
 */
final readonly class ClassReflector implements Reflector
{
    use HasAttributes;

    private PHPReflectionClass $reflectionClass;

    /**
     * @param class-string<TClassName>|TClassName|PHPReflectionClass<TClassName> $reflectionClass
     */
    public function __construct(string|object $reflectionClass)
    {
        if (is_string($reflectionClass)) {
            $reflectionClass = new PHPReflectionClass($reflectionClass);
        } elseif (! $reflectionClass instanceof PHPReflectionClass) {
            $reflectionClass = new PHPReflectionClass($reflectionClass);
        }

        $this->reflectionClass = $reflectionClass;
    }

    public function getReflection(): PHPReflectionClass
    {
        return $this->reflectionClass;
    }

    public function getParent(): ?ClassReflector
    {
        if ($parentClass = $this->reflectionClass->getParentClass()) {
            return new ClassReflector($parentClass);
        }

        return null;
    }

    /** @return Generator<\Tempest\Reflection\TypeReflector> */
    public function getInterfaces(): Generator
    {
        foreach ($this->reflectionClass->getInterfaces() as $interface) {
            yield new TypeReflector($interface);
        }
    }

    /** @return Generator<PropertyReflector> */
    public function getPublicProperties(): Generator
    {
        foreach ($this->reflectionClass->getProperties(PHPReflectionProperty::IS_PUBLIC) as $property) {
            yield new PropertyReflector($property);
        }
    }

    /** @return Generator<PropertyReflector> */
    public function getProperties(): Generator
    {
        foreach ($this->reflectionClass->getProperties() as $property) {
            yield new PropertyReflector($property);
        }
    }

    /** @return Generator<MethodReflector> */
    public function getPublicMethods(): Generator
    {
        foreach ($this->reflectionClass->getMethods(PHPReflectionMethod::IS_PUBLIC) as $method) {
            yield new MethodReflector($method);
        }
    }

    public function getProperty(string $name): PropertyReflector
    {
        return new PropertyReflector(new PHPReflectionProperty($this->reflectionClass->getName(), $name));
    }

    public function hasProperty(string $name): bool
    {
        return $this->reflectionClass->hasProperty($name);
    }

    /**
     * @return class-string<TClassName>
     */
    public function getName(): string
    {
        return $this->reflectionClass->getName();
    }

    public function getShortName(): string
    {
        return $this->reflectionClass->getShortName();
    }

    public function getFileName(): string
    {
        return $this->reflectionClass->getFileName();
    }

    public function getType(): TypeReflector
    {
        return new TypeReflector($this->reflectionClass);
    }

    public function getConstructor(): ?MethodReflector
    {
        $constructor = $this->reflectionClass->getConstructor();

        if ($constructor === null) {
            return null;
        }

        return new MethodReflector($constructor);
    }

    public function getMethod(string $name): MethodReflector
    {
        return new MethodReflector($this->reflectionClass->getMethod($name));
    }

    public function hasMethod(string $name): bool
    {
        return $this->reflectionClass->hasMethod($name);
    }

    public function isInstantiable(): bool
    {
        return $this->reflectionClass->isInstantiable();
    }

    public function newInstanceWithoutConstructor(): object
    {
        return $this->reflectionClass->newInstanceWithoutConstructor();
    }

    public function newInstanceArgs(array $args = []): object
    {
        return $this->reflectionClass->newInstanceArgs($args);
    }

    public function callStatic(string $method, mixed ...$args): mixed
    {
        $className = $this->getName();

        return $className::$method(...$args);
    }

    public function is(string $className): bool
    {
        return $this->getType()->matches($className);
    }

    public function implements(string $interface): bool
    {
        return $this->isInstantiable() && $this->getType()->matches($interface);
    }
}
