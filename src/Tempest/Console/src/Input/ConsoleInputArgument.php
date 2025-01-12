<?php

declare(strict_types=1);

namespace Tempest\Console\Input;

use function Tempest\Support\str;

final class ConsoleInputArgument
{
    public function __construct(
        public ?string $name,
        public ?int $position,
        public mixed $value,
        public bool $isPositional = false,
    ) {
    }

    public static function fromString(string $argument, ?int $position = null): ConsoleInputArgument
    {
        if (str_starts_with($argument, '--')) {
            [$key, $value] = self::parseNamedArgument($argument);

            return new ConsoleInputArgument(
                name: $key,
                position: null,
                value: $value,
            );
        }

        if (str_starts_with($argument, '-')) {
            return new ConsoleInputArgument(
                name: $argument,
                position: null,
                value: true,
            );
        }

        return new ConsoleInputArgument(
            name: null,
            position: $position,
            value: $argument,
            isPositional: true,
        );
    }

    public function matches(string $name): bool
    {
        if ($this->name === null) {
            return false;
        }

        return ltrim($this->name, '-') === ltrim($name, '-');
    }

    /**
     * @return array{0: string, 1: mixed}
     */
    private static function parseNamedArgument(string $argument): array
    {
        preg_match('/--(?<name>[\w-]+)((?<hasValue>=)\"?(?<value>(.*?))(\"|$))?/', $argument, $matches);

        $name = str($matches['name'] ?? null)->kebab()->toString();
        $normalizedName = str($matches['name'] ?? null)->kebab()->replaceStart('no-', '')->toString();
        $isNegative = str($matches['name'] ?? null)->kebab()->startsWith('no-');
        $hasValue = $matches['hasValue'] ?? null;
        $value = $matches['value'] ?? null;

        if (! $hasValue) {
            return [$normalizedName, ! $isNegative];
        }

        $value = match ($value) {
            'true' => ! $isNegative,
            'false' => $isNegative,
            '' => null,
            default => $value,
        };

        return [
            is_bool($value) ? $normalizedName : $name,
            $value,
        ];
    }
}
