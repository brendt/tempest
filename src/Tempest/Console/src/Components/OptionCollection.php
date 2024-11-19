<?php

declare(strict_types=1);

namespace Tempest\Console\Components;

use Countable;
use Iterator;
use function Tempest\Support\arr;
use Tempest\Support\ArrayHelper;

final class OptionCollection implements Iterator, Countable
{
    /** @var array<Option> */
    private readonly array $options;

    /** @var array<Option> */
    private array $filteredOptions;

    /** @var array<Option> */
    private array $selectedOptions = [];

    private int $activeOption = 0;

    public function __construct(iterable $options)
    {
        $this->options = arr($options)
            ->map(fn (mixed $value, string|int $key) => new Option($key, $value))
            ->toArray();

        $this->filter(null);
    }

    public function filter(?string $query): void
    {
        $previouslyActiveOption = $this->getActive();
        $previouslySelectedOptions = $this->getSelectedOptions();

        $this->filteredOptions = arr($this->options)
            ->filter(fn (Option $option) => empty($query) || str_contains(mb_strtolower($option->value), mb_strtolower(trim($query))))
            ->values()
            ->toArray();

        $this->selectedOptions = array_filter($this->filteredOptions, fn (Option $option) => in_array($option, $previouslySelectedOptions, strict: true));
        $this->activeOption = array_search($previouslyActiveOption ?? $this->filteredOptions[0], $this->filteredOptions, strict: true) ?: 0;
    }

    public function count(): int
    {
        return count($this->filteredOptions);
    }

    public function previous(): void
    {
        $this->activeOption -= 1;

        if ($this->activeOption < 0) {
            $this->activeOption = $this->count() - 1;
        }
    }

    public function next(): void
    {
        $this->activeOption += 1;

        if ($this->activeOption > $this->count() - 1) {
            $this->activeOption = 0;
        }
    }

    public function toggleCurrent(): void
    {
        if (! $active = $this->getActive()) {
            return;
        }

        if (! $this->isSelected($active)) {
            $this->selectedOptions[] = $active;
        } else {
            $this->selectedOptions = array_filter($this->selectedOptions, fn (Option $option) => ! $active->equals($option));
        }
    }

    public function getOptions(): ArrayHelper
    {
        return arr($this->filteredOptions)->values();
    }

    public function rawOptions(): array
    {
        return array_map(fn (Option $option) => $option->value, $this->options);
    }

    /** @var array<Option> */
    public function getSelectedOptions(): array
    {
        return $this->selectedOptions;
    }

    /** @return array<Option> */
    public function getScrollableSection(int $offset = 0, ?int $count = null): array
    {
        return array_slice(
            $this->filteredOptions,
            $offset,
            $count,
            preserve_keys: true,
        );
    }

    public function getCurrentIndex(): int
    {
        return array_search($this->activeOption, array_keys($this->filteredOptions), strict: true) ?: 0;
    }

    public function isSelected(Option $option): bool
    {
        return (bool) arr($this->getSelectedOptions())->first(fn (Option $other) => $option->equals($other));
    }

    public function isActive(Option $option): bool
    {
        return (bool) $this->current()?->equals($option);
    }

    public function isList(): bool
    {
        return array_is_list($this->options);
    }

    public function getActive(): ?Option
    {
        return $this->filteredOptions[$this->activeOption] ?? null;
    }

    public function current(): ?Option
    {
        return $this->getActive();
    }

    public function key(): mixed
    {
        return $this->activeOption;
    }

    public function valid(): bool
    {
        return isset($this->filteredOptions[$this->activeOption]);
    }

    public function rewind(): void
    {
        $this->activeOption = 0;
    }
}
