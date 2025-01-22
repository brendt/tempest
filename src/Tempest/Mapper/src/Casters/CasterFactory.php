<?php

declare(strict_types=1);

namespace Tempest\Mapper\Casters;

use BackedEnum;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use ReflectionException;
use Tempest\Mapper\Caster;
use Tempest\Mapper\CastWith;
use Tempest\Reflection\PropertyReflector;
use function Tempest\get;

final readonly class CasterFactory
{
    public function forProperty(PropertyReflector $property): ?Caster
    {
        $type = $property->getType();

        // Get CastWith from the property
        $castWith = $property->getAttribute(CastWith::class);

        // Get CastWith from the property's type if there's no property-defined CastWith
        if ($castWith === null) {
            try {
                $castWith = $type->asClass()->getAttribute(CastWith::class, recursive: true);
            } catch (ReflectionException) {
                // Could not resolve CastWith from the type
            }
        }

        // Return the caster if defined with CastWith
        if ($castWith !== null) {
            // Resolve the caster from the container
            return get($castWith->className);
        }

        $typeName = $type->getName();

        // Check if backed enum
        if ($type->matches(BackedEnum::class)) {
            return new EnumCaster($typeName);
        }

        // Try a built-in caster
        $builtInCaster = match ($type->getName()) {
            'int' => new IntegerCaster(),
            'float' => new FloatCaster(),
            'bool' => new BooleanCaster(),
            DateTimeImmutable::class, DateTimeInterface::class, DateTime::class => DateTimeCaster::fromProperty($property),
            default => null,
        };

        if ($builtInCaster !== null) {
            return $builtInCaster;
        }

        // If the type's a class, we'll cast it with the generic object caster
        if ($type->isClass()) {
            return new ObjectCaster($type);
        }

        return null;
    }
}
