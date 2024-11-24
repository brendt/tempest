<?php

declare(strict_types=1);

namespace Tempest\Core;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use function Tempest\Support\arr;
use Traversable;

final class DiscoveryItems implements IteratorAggregate, Countable
{
    public function __construct(
        private array $items = [],
    ) {
    }

    public function add(DiscoveryLocation $location, mixed $value): self
    {
        $this->items[$location->path] ??= [];
        $this->items[$location->path][] = $value;

        return $this;
    }

    public function hasLocation(DiscoveryLocation $location): bool
    {
        return array_key_exists($location->path, $this->items);
    }

    public function isLoaded(): bool
    {
        return $this->items !== [];
    }

    public function onlyVendor(): self
    {
        return new self(
            arr($this->items)
                ->filter(fn (array $items, string $path) => str_contains($path, '/vendor/') || str_contains($path, '\\vendor\\'))
                ->toArray(),
        );
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator(arr($this->items)->flatten(1)->toArray());
    }

    public function count(): int
    {
        return iterator_count($this->items);
    }

    public function __serialize(): array
    {
        return $this->items;
    }

    public function __unserialize(array $data): void
    {
        $this->items = $data;
    }
}
