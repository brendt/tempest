<?php

declare(strict_types=1);

namespace Tempest\Database\Casters;

use Tempest\Mapper\Caster;

final class RelationCaster implements Caster
{
    public function cast(mixed $input): mixed
    {
        return $input;
    }
}
