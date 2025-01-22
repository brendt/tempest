<?php

declare(strict_types=1);

namespace Tempest\View\Elements;

use Tempest\View\Element;
use Tempest\View\View;
use Tempest\View\WrapsElement;

/** @phpstan-require-implements \Tempest\View\Element */
trait IsElement
{
    private View $view;

    /** @var Element[] */
    private array $children = [];

    private ?Element $parent = null;

    private ?Element $previous = null;

    private array $attributes = [];

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function hasAttribute(string $name): bool
    {
        $name = ltrim($name, ':');

        return
            array_key_exists(":{$name}", $this->attributes) ||
            array_key_exists($name, $this->attributes);
    }

    public function getAttribute(string $name): string|null
    {
        $name = ltrim($name, ':');

        if ($this instanceof WrapsElement) {
            $value = $this->getWrappingElement()->getAttribute($name);
        }

        return
            $value
            ?? $this->attributes[":{$name}"]
            ?? $this->attributes[$name]
            ?? null;
    }

    public function setAttribute(string $name, string $value): self
    {
        if ($this instanceof WrapsElement) {
            $this->getWrappingElement()->setAttribute($name, $value);
        }

        $this->unsetAttribute($name);

        $this->attributes[$name] = $value;

        return $this;
    }

    public function consumeAttribute(string $name): string|null
    {
        $value = $this->getAttribute($name);

        $this->unsetAttribute($name);

        return $value;
    }

    public function unsetAttribute(string $name): self
    {
        $name = ltrim($name, ':');

        unset($this->attributes[$name], $this->attributes[":{$name}"]);

        return $this;
    }

    public function setPrevious(?Element $previous): self
    {
        $this->previous = $previous;

        return $this;
    }

    public function getPrevious(): ?Element
    {
        return $this->previous;
    }

    public function setParent(?Element $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function getParent(): ?Element
    {
        return $this->parent;
    }

    /** @return \Tempest\View\Element[] */
    public function getChildren(): array
    {
        return $this->children;
    }

    /** @param \Tempest\View\Element[] $children */
    public function setChildren(array $children): self
    {
        $previous = null;

        foreach ($children as $child) {
            $child
                ->setParent($this)
                ->setPrevious($previous);

            $previous = $child;
        }

        $this->children = $children;

        return $this;
    }

    /**
     * @template T of \Tempest\View\Element
     * @param class-string<T> $elementClass
     * @return T|null
     */
    public function unwrap(string $elementClass): ?Element
    {
        if ($this instanceof $elementClass) {
            return $this;
        }

        if ($this instanceof WrapsElement) {
            return $this->getWrappingElement()->unwrap($elementClass);
        }

        return null;
    }
}
