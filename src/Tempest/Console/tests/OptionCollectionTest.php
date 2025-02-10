<?php

declare(strict_types=1);

namespace Tempest\Console\Tests;

use PHPUnit\Framework\TestCase;
use Tempest\Console\Components\Option;
use Tempest\Console\Components\OptionCollection;

/**
 * @internal
 */
final class OptionCollectionTest extends TestCase
{
    public function test_filter(): void
    {
        $options = new OptionCollection(['foo', 'bar', 'baz']);

        $options->filter('ba');
        $this->assertCount(2, $options->getOptions());
        $this->assertSame('bar', $options->getActive()->value);

        $options->filter(null);
        $this->assertCount(3, $options->getOptions());
        $this->assertSame('bar', $options->getActive()->value);

        $options->filter('bar');
        $this->assertCount(1, $options->getOptions());
        $this->assertSame('bar', $options->getOptions()->first()->value);

        $options->filter('ergljherkigjerg');
        $this->assertCount(0, $options->getOptions());
        $this->assertSame(null, $options->getActive());
    }

    public function test_keeps_active_on_filter(): void
    {
        $options = new OptionCollection(['foo', 'bar', 'baz']);

        $options->next();
        $options->next();
        $this->assertSame('baz', $options->getActive()->value);

        $options->filter('ba');
        $this->assertCount(2, $options->getOptions());
        $this->assertSame('baz', $options->getActive()->value);

        $options->filter('baz');
        $this->assertSame('baz', $options->getActive()->value);

        $options->filter('bazz');
        $this->assertSame(null, $options->getActive());
    }

    public function test_navigate(): void
    {
        $options = new OptionCollection(['foo', 'bar', 'baz']);

        $options->next();
        $this->assertSame('bar', $options->getActive()->value);

        $options->next();
        $this->assertSame('baz', $options->getActive()->value);

        $options->next();
        $this->assertSame('foo', $options->getActive()->value);

        $options->previous();
        $this->assertSame('baz', $options->getActive()->value);

        $options->previous();
        $this->assertSame('bar', $options->getActive()->value);

        $options->previous();
        $this->assertSame('foo', $options->getActive()->value);
    }

    public function test_select(): void
    {
        $options = new OptionCollection(['foo', 'bar', 'baz']);

        $options->next();
        $options->toggleCurrent();
        $this->assertSame(['bar'], $this->toValues($options->getSelectedOptions()));

        $options->toggleCurrent();
        $this->assertSame([], $this->toValues($options->getSelectedOptions()));

        $options->toggleCurrent();
        $options->next();
        $options->toggleCurrent();
        $this->assertSame(['bar', 'baz'], $this->toValues($options->getSelectedOptions()));
    }

    public function test_select_and_filter(): void
    {
        $options = new OptionCollection(['foo', 'bar', 'baz']);

        $options->toggleCurrent();
        $options->next();
        $options->toggleCurrent();
        $options->next();
        $options->toggleCurrent();
        $this->assertSame(['foo', 'bar', 'baz'], $this->toValues($options->getSelectedOptions()));

        $options->filter('ba');
        $this->assertSame(['bar', 'baz'], $this->toValues($options->getSelectedOptions()));

        $options->filter(null);
        $this->assertSame(['bar', 'baz'], $this->toValues($options->getSelectedOptions()));

        $options->filter('r');
        $this->assertSame(['bar'], $this->toValues($options->getSelectedOptions()));
        $this->assertSame('bar', $options->current()->value);
    }

    public function test_scrollable_section(): void
    {
        $options = new OptionCollection(['foo', 'bar', 'baz', 'qux', 'quux']);

        $this->assertCount(2, $options->getScrollableSection(1, 2));
        $this->assertSame(['bar', 'baz'], $this->toValues($options->getScrollableSection(1, 2)));
    }

    public function test_enum_options(): void
    {
        $options = new OptionCollection(OptionCollectionEnum::cases());

        $options->next();
        $this->assertSame('OPT_2', $options->getActive()->displayValue);

        $options->next();
        $this->assertSame('OPT_3', $options->getActive()->displayValue);

        $options->next();
        $this->assertSame('OPT_1', $options->getActive()->displayValue);
    }

    private function toValues(array $options): array
    {
        return array_map(fn (Option $option) => $option->value, array_values($options));
    }
}
