<?php

declare(strict_types=1);

namespace Tempest\Generation\Tests\Fixtures;

final class ClassWithDummyStringToBeReplacedByFqcn
{
    public function dummy(): string
    {
        return 'fqcn-to-be-replaced';
    }
}
