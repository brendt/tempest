<?php

declare(strict_types=1);

namespace Tempest\Console\Components\Concerns;

use Tempest\Console\InteractiveConsoleComponent;

/**
 * @mixin InteractiveConsoleComponent
 * @phpstan-require-implements InteractiveConsoleComponent
 */
trait HasErrors
{
    /** @var string[] */
    private array $errors = [];

    public function setErrors(array $errors): self
    {
        $this->errors = $errors;

        return $this;
    }
}
