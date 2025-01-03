<?php

declare(strict_types=1);

namespace Tempest\Support\Enums;

use BackedEnum;

trait InvokableCases
{
    /**
     * Returns the enum's value when it's invoked
     */
    public function __invoke(): string
    {
        return $this instanceof BackedEnum
            ? $this->value
            : $this->name;
    }
}
