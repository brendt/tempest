<?php

declare(strict_types=1);

namespace Tempest\View\Attributes;

use Tempest\View\Attribute;
use Tempest\View\Element;
use Tempest\View\Elements\PhpDataElement;
use Tempest\View\Elements\ViewComponentElement;

final readonly class DataAttribute implements Attribute
{
    public function __construct(
        private string $name,
    ) {
    }

    public function apply(Element $element): Element
    {
        if (! $element instanceof ViewComponentElement) {
            return $element;
        }

        return new PhpDataElement(
            $this->name,
            $element->getAttribute($this->name),
            $element,
        );
    }
}
