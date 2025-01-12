<?php

declare(strict_types=1);

namespace Tempest\Container\Tests\Fixtures;

use Tempest\Container\Tag;

final readonly class DependencyWithBuiltinDependencies
{
    public function __construct(
        #[Tag('builtin-dependency-array')]
        public array $arrayValue,
        #[Tag('builtin-dependency-string')]
        public string $stringValue,
        #[Tag('builtin-dependency-bool')]
        public bool $boolValue,
    ) {
    }
}
