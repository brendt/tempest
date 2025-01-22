<?php

declare(strict_types=1);

namespace Tempest\Core;

use Tempest\Container\Container;

final readonly class Tempest
{
    public static function boot(
        ?string $root = null,
        /** @var \Tempest\Discovery\DiscoveryLocation[] $discoveryLocations */
        array $discoveryLocations = [],
    ): Container {
        $root ??= getcwd();

        // Kernel
        return Kernel::boot(
            root: $root,
            discoveryLocations: $discoveryLocations,
        )->container;
    }
}
