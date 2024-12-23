<?php

declare(strict_types=1);

namespace Tempest\Console\Components\Static;

use Tempest\Console\Components\Option;
use Tempest\Console\Components\OptionCollection;
use Tempest\Console\Console;
use Tempest\Console\StaticConsoleComponent;
use UnitEnum;
use function Tempest\Support\str;

final readonly class StaticSingleChoiceComponent implements StaticConsoleComponent
{
    private OptionCollection $options;

    public function __construct(
        public string $label,
        iterable $options,
        public null|int|UnitEnum|string $default = null,
    ) {
        $this->options = new OptionCollection($options);
    }

    public function render(Console $console): null|int|UnitEnum|string
    {
        if (! $console->supportsPrompting()) {
            return $this->default;
        }

        $console->write("<style='bold fg-blue'>{$this->label}</style> ");

        $prompt = $this->options->getOptions()
            ->map(
                fn (Option $option, int $index) => str($index)
                        ->when(
                            condition: $option->key === $this->default || $option->value === $this->default,
                            callback: fn ($s) => $s->wrap('<style="fg-blue">', '</style>'),
                        )
                        ->wrap('[', ']')
                        ->prepend('- ')
                        ->append(' ', (string) $option->displayValue)
                        ->toString(),
            )
            ->implode(PHP_EOL)
            ->toString();

        $console->write(PHP_EOL . $prompt . PHP_EOL);

        $answer = trim($console->readln());

        if ($answer === '' && $this->default) {
            return $this->default;
        }

        $selectedOption = $this->options->getOptions()->first(function (Option $option, int $index) use ($answer) {
            if ($answer === $option->displayValue) {
                return true;
            }

            if ($answer === $option->value) {
                return true;
            }

            if ($this->options->getOptions()->isList() && $answer === (string) $index) {
                return true;
            }

            if ($this->options->getOptions()->isList() && $answer === (string) $option->key) {
                return true;
            }

            return false;
        });

        if ($selectedOption !== null) {
            return $selectedOption->value;
        }

        return $this->render($console);
    }
}
