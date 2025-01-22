<?php

declare(strict_types=1);

namespace Tempest\Discovery;

/** @phpstan-require-implements \Tempest\Discovery\Discovery */
trait IsDiscovery
{
    private DiscoveryItems $discoveryItems;

    public function getItems(): DiscoveryItems
    {
        return $this->discoveryItems;
    }

    public function setItems(DiscoveryItems $items): void
    {
        $this->discoveryItems = $items;
    }
}
