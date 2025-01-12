<?php

declare(strict_types=1);

namespace Tempest\Reflection;

use Exception;
use Generator;
use ReflectionClass as PHPReflectionClass;
use ReflectionIntersectionType as PHPReflectionIntersectionType;
use ReflectionNamedType as PHPReflectionNamedType;
use ReflectionParameter as PHPReflectionParameter;
use ReflectionProperty as PHPReflectionProperty;
use ReflectionType as PHPReflectionType;
use ReflectionUnionType as PHPReflectionUnionType;
use Reflector as PHPReflector;

final readonly class TypeReflector implements Reflector
{
    private const array BUILTIN_VALIDATION = [
        'array' => 'is_array',
        'bool' => 'is_bool',
        'callable' => 'is_callable',
        'float' => 'is_float',
        'int' => 'is_int',
        'null' => 'is_null',
        'object' => 'is_object',
        'resource' => 'is_resource',
        'string' => 'is_string',
        // these are handled explicitly
        'false' => null,
        'mixed' => null,
        'never' => null,
        'true' => null,
        'void' => null,
    ];

    private string $definition;

    private string $cleanDefinition;

    private bool $isNullable;

    public function __construct(
        private PHPReflector|PHPReflectionType|string $reflector,
    ) {
        $this->definition = $this->resolveDefinition($this->reflector);
        $this->isNullable = $this->resolveIsNullable($this->reflector);
        $this->cleanDefinition = str_replace('?', '', $this->definition);
    }

    public function asClass(): ClassReflector
    {
        return new ClassReflector($this->cleanDefinition);
    }

    public function equals(string|TypeReflector $type): bool
    {
        if (is_string($type)) {
            $type = new TypeReflector($type);
        }

        return $this->definition === $type->definition;
    }

    public function accepts(mixed $input): bool
    {
        if ($this->isNullable && $input === null) {
            return true;
        }

        if ($this->isBuiltIn()) {
            return match ($this->cleanDefinition) {
                'false' => $input === false,
                'mixed' => true,
                'never' => false,
                'true' => $input === true,
                'void' => false,
                default => self::BUILTIN_VALIDATION[$this->cleanDefinition]($input),
            };
        }

        if ($this->isClass()) {
            if (is_string($input)) {
                return $this->matches($input);
            }

            $cleanDefinition = $this->cleanDefinition;

            return $input instanceof $cleanDefinition;
        }

        if ($this->isIterable()) {
            return is_iterable($input);
        }

        if (str_contains($this->definition, '|')) {
            return array_any($this->split(), static fn ($type) => $type->accepts($input));
        }

        if (str_contains($this->definition, '&')) {
            return array_all($this->split(), static fn ($type) => $type->accepts($input));
        }

        return false;
    }

    public function matches(string $className): bool
    {
        return is_a($this->cleanDefinition, $className, true);
    }

    public function getName(): string
    {
        return $this->definition;
    }

    public function getShortName(): string
    {
        $parts = explode('\\', $this->definition);

        return $parts[array_key_last($parts)];
    }

    public function isBuiltIn(): bool
    {
        return isset(self::BUILTIN_VALIDATION[$this->cleanDefinition]);
    }

    public function isClass(): bool
    {
        return class_exists($this->cleanDefinition);
    }

    public function isInterface(): bool
    {
        return interface_exists($this->cleanDefinition);
    }

    public function isIterable(): bool
    {
        return in_array($this->cleanDefinition, [
            'array',
            'iterable',
            Generator::class,
        ]);
    }

    public function isNullable(): bool
    {
        return $this->isNullable;
    }

    /** @return self[] */
    public function split(): array
    {
        return array_map(
            fn (string $part) => new self($part),
            preg_split('/[&|]/', $this->definition),
        );
    }

    private function resolveDefinition(PHPReflector|PHPReflectionType|string $reflector): string
    {
        if (is_string($reflector)) {
            return $reflector;
        }

        if (
            $reflector instanceof PHPReflectionParameter
            || $reflector instanceof PHPReflectionProperty
        ) {
            return $this->resolveDefinition($reflector->getType());
        }

        if ($reflector instanceof PHPReflectionClass) {
            return $reflector->getName();
        }

        if ($reflector instanceof PHPReflectionNamedType) {
            return $reflector->getName();
        }

        if ($reflector instanceof PHPReflectionUnionType) {
            return implode('|', array_map(
                fn (PHPReflectionType $reflectionType) => $this->resolveDefinition($reflectionType),
                $reflector->getTypes(),
            ));
        }

        if ($reflector instanceof PHPReflectionIntersectionType) {
            return implode('&', array_map(
                fn (PHPReflectionType $reflectionType) => $this->resolveDefinition($reflectionType),
                $reflector->getTypes(),
            ));
        }

        throw new Exception('Could not resolve type');
    }

    private function resolveIsNullable(PHPReflectionType|PHPReflector|string $reflector): bool
    {
        if (is_string($reflector)) {
            return str_contains($this->definition, '?') || str_contains($this->definition, 'null');
        }

        if (
            $reflector instanceof PHPReflectionParameter
            || $reflector instanceof PHPReflectionProperty
        ) {
            return $reflector->getType()->allowsNull();
        }

        if ($reflector instanceof PHPReflectionType) {
            return $reflector->allowsNull();
        }

        return false;
    }
}
