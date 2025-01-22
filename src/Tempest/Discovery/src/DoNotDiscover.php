<?php

declare(strict_types=1);

namespace Tempest\Discovery;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class DoNotDiscover
{
    public function __construct(
        /**
         * Allows the specified `Discovery` classes to still discover this class.
         * @var array<class-string<\Tempest\Discovery\Discovery>>
         */
        public array $except = [],
    ) {
    }
}
