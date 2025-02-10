<?php

declare(strict_types=1);

namespace Tempest\Console\Components;

enum ComponentState
{
    /**
     * Component is available for input.
     */
    case ACTIVE;

    /**
     * There are validation errors.
     */
    case ERROR;

    /**
     * Input was cancelled.
     */
    case CANCELLED;

    /**
     * Input was submitted.
     */
    case DONE;

    /**
     * Input is blocked.
     */
    case BLOCKED;

    public function isFinished(): bool
    {
        return match ($this) {
            self::ACTIVE, self::ERROR, self::BLOCKED => false,
            default => true,
        };
    }
}
