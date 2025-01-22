<?php

declare(strict_types=1);

namespace Tempest\Validation;

use Closure;
use Tempest\Reflection\ClassReflector;
use Tempest\Validation\Exceptions\InvalidValueException;
use Tempest\Validation\Exceptions\ValidationException;
use function Tempest\Support\arr;

final readonly class Validator
{
    public function validate(object $object): void
    {
        $class = new ClassReflector($object);

        $failingRules = [];

        foreach ($class->getPublicProperties() as $property) {
            $rules = $property->getAttributes(Rule::class);

            if (! $property->isInitialized($object)) {
                continue;
            }

            $value = $property->getValue($object);

            try {
                $this->validateValue($value, $rules);
            } catch (InvalidValueException $invalidValueException) {
                $failingRules[$property->getName()] = $invalidValueException->failingRules;
            }
        }

        if ($failingRules !== []) {
            throw new ValidationException($object, $failingRules);
        }
    }

    public function validateValue(mixed $value, Closure|Rule|array $rules): void
    {
        $failingRules = [];

        foreach (arr($rules) as $rule) {
            $rule = $this->convertToRule($rule, $value);

            if (! $rule->isValid($value)) {
                $failingRules[] = $rule;
            }
        }

        if ($failingRules !== []) {
            throw new InvalidValueException($value, $failingRules);
        }
    }

    private function convertToRule(Rule|Closure $rule, mixed $value): Rule
    {
        if ($rule instanceof Rule) {
            return $rule;
        }

        $result = $rule($value);

        [$isValid, $message] = match (true) {
            is_string($result) => [false, $result],
            $result === false => [false, 'Value did not pass validation.'],
            default => [true, ''],
        };

        return new readonly class ($isValid, $message) implements Rule {
            public function __construct(
                private bool $isValid,
                private string $message,
            ) {
            }

            public function isValid(mixed $value): bool
            {
                return $this->isValid;
            }

            public function message(): string
            {
                return $this->message;
            }
        };
    }
}
