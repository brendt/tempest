<?php

declare(strict_types=1);

namespace Tempest\Discovery;

use Tempest\Reflection\ClassReflector;

interface Discovery
{
    public function discover(DiscoveryLocation $location, ClassReflector $class): void;

    public function getItems(): DiscoveryItems;

    public function setItems(DiscoveryItems $items): void;

    public function apply(): void;
}
