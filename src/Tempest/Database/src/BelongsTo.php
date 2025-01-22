<?php

declare(strict_types=1);

namespace Tempest\Database;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class BelongsTo
{
    public function __construct(public string $localPropertyName, public string $inversePropertyName = 'id')
    {
    }
}
