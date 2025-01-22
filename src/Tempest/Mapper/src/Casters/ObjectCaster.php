<?php

declare(strict_types=1);

namespace Tempest\Mapper\Casters;

use Tempest\Mapper\Caster;
use Tempest\Reflection\TypeReflector;
use Throwable;

final readonly class ObjectCaster implements Caster
{
    public function __construct(
        private TypeReflector $type,
    ) {
    }

    public function cast(mixed $input): mixed
    {
        try {
            return $this->type->asClass()->newInstanceArgs([$input]);
        } catch (Throwable) {
            return $input;
        }
    }
}
