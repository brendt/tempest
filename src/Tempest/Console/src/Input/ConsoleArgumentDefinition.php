<?php

declare(strict_types=1);

namespace Tempest\Console\Input;

use BackedEnum;
use Tempest\Console\ConsoleArgument;
use Tempest\Reflection\ParameterReflector;
use function Tempest\Support\str;

final readonly class ConsoleArgumentDefinition
{
    public function __construct(
        public string $name,
        public string $type,
        public mixed $default,
        public bool $hasDefault,
        public int $position,
        public ?string $description = null,
        public array $aliases = [],
        public ?string $help = null,
    ) {
    }

    public static function fromParameter(ParameterReflector $parameter): ConsoleArgumentDefinition
    {
        $attribute = $parameter->getAttribute(ConsoleArgument::class);
        $type = $parameter->getType();
        $default = $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null;
        $boolean = $type->getName() === 'bool' || is_bool($default);

        return new ConsoleArgumentDefinition(
            name: static::normalizeName($attribute->name ?? $parameter->getName(), boolean: $boolean),
            type: $type->getName(),
            default: $default,
            hasDefault: $parameter->isDefaultValueAvailable(),
            position: $parameter->getPosition(),
            description: $attribute?->description,
            aliases: $attribute->aliases ?? [],
            help: $attribute?->help,
        );
    }

    public function matchesArgument(ConsoleInputArgument $argument): bool
    {
        if ($argument->position === $this->position) {
            return true;
        }

        if (! $argument->name) {
            return false;
        }

        return array_any(
            array: [$this->name, ...$this->aliases],
            callback: fn ($match) => $argument->matches(static::normalizeName($match, $this->type === 'bool')),
        );
    }

    private static function normalizeName(string $name, bool $boolean): string
    {
        $normalizedName = str($name)->kebab();

        if ($boolean) {
            $normalizedName->replaceStart('no-', '');
        }

        return $normalizedName->toString();
    }

    public function isBackedEnum(): bool
    {
        return is_subclass_of($this->type, BackedEnum::class);
    }
}
