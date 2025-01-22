<?php

declare(strict_types=1);

namespace Tempest\Support;

/** @internal */
trait IsIterable
{
    public function current(): mixed
    {
        return current($this->array);
    }

    public function next(): void
    {
        next($this->array);
    }

    public function key(): string|int|null
    {
        return key($this->array);
    }

    public function valid(): bool
    {
        return $this->current() !== false;
    }

    public function rewind(): void
    {
        reset($this->array);
    }

    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->array);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->array[$offset] ?? throw new OffsetDoesNotExist();
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->array[] = $value;
        } else {
            $this->array[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->array[$offset]);
    }

    public function serialize(): string
    {
        return serialize($this->array);
    }

    public function unserialize(string $data): void
    {
        $this->array = unserialize($data);
    }

    public function __serialize(): array
    {
        return [
            'array' => $this->array,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->array = $data['array'];
    }

    public function count(): int
    {
        return count($this->array);
    }
}
