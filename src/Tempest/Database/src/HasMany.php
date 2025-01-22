<?php

declare(strict_types=1);

namespace Tempest\Database;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class HasMany
{
    /** @param null|class-string $inverseClassName */
    public function __construct(
        public string $inversePropertyName,
        public ?string $inverseClassName = null,
        public string $localPropertyName = 'id',
    ) {
    }
}
