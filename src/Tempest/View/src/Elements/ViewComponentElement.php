<?php

declare(strict_types=1);

namespace Tempest\View\Elements;

use Tempest\View\Element;
use Tempest\View\ViewComponent;

final class ViewComponentElement implements Element
{
    use IsElement;

    public function __construct(
        private readonly ViewComponent $viewComponent,
        private readonly array $attributes,
    ) {
    }

    public function getViewComponent(): ViewComponent
    {
        return $this->viewComponent;
    }

    public function getSlot(string $name = 'slot'): ?Element
    {
        foreach ($this->getChildren() as $child) {
            if (! $child instanceof SlotElement) {
                continue;
            }

            if ($child->matches($name)) {
                return $child;
            }
        }

        if ($name === 'slot') {
            $elements = [];

            foreach ($this->getChildren() as $child) {
                if ($child instanceof SlotElement) {
                    continue;
                }

                $elements[] = $child;
            }

            return new CollectionElement($elements);
        }

        return null;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function hasAttribute(string $name): bool
    {
        $name = ltrim($name, ':');

        return array_key_exists($name, $this->attributes)
            || array_key_exists(":{$name}", $this->attributes);
    }
    
    public function getAttribute(string $name): string|null
    {
        $name = ltrim($name, ':');

        return $this->attributes[":{$name}"]
            ?? $this->attributes[$name]
            ?? null;
    }

    public function getData(?string $key = null): mixed
    {
        if ($key && $this->hasAttribute($key)) {
            return $this->getAttribute($key);
        }

        $parentData = $this->getParent()?->getData() ?? [];

        $data = [...$this->attributes, ...$this->view->getData(), ...$parentData, ...$this->data];

        if ($key) {
            return $data[$key] ?? null;
        }

        return $data;
    }

    private function eval(string $eval): mixed
    {
        $data = $this->getData();

        extract($data, flags: EXTR_SKIP);

        /** @phpstan-ignore-next-line */
        return eval("return {$eval};");
    }

    public function __get(string $name)
    {
        return $this->getData($name) ?? $this->view->{$name};
    }

    public function __call(string $name, array $arguments)
    {
        return $this->view->{$name}(...$arguments);
    }

    public function compile(): string
    {
        return preg_replace_callback(
            pattern: '/<x-slot\s*(name="(?<name>\w+)")?((\s*\/>)|><\/x-slot>)/',
            callback: function ($matches) {
                $name = $matches['name'] ?: 'slot';

                $slot = $this->getSlot($name);

                if ($slot === null) {
                    return $matches[0];
                }

                return $slot->compile();
            },
            subject: $this->getViewComponent()->render($this),
        );
    }
}
