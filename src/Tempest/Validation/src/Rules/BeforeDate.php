<?php

declare(strict_types=1);

namespace Tempest\Validation\Rules;

use Attribute;
use DateTimeImmutable;
use DateTimeInterface;
use Tempest\Validation\Rule;

#[Attribute]
final readonly class BeforeDate implements Rule
{
    private DateTimeImmutable $date;

    public function __construct(
        DateTimeInterface|string $date = 'now',
        private bool $inclusive = false,
    ) {
        $this->date = $date instanceof DateTimeInterface
            ? DateTimeImmutable::createFromInterface($date)
            : new DateTimeImmutable($date);
    }

    public function isValid(mixed $value): bool
    {
        return ! new AfterDate($this->date, $this->inclusive)->isValid($value);
    }

    public function message(): string
    {
        $message[] = 'Value must be a date before';

        if ($this->inclusive) {
            $message[] = 'or equal to';
        }

        $message[] = $this->date->format('Y-m-d H:i:s');

        return implode(' ', $message);
    }
}
