<?php

declare(strict_types=1);

namespace Tempest\Console\Components\Static;

use Tempest\Console\Components\Option;
use Tempest\Console\Components\OptionCollection;
use Tempest\Console\Console;
use Tempest\Console\StaticConsoleComponent;
use Tempest\Support\ArrayHelper;
use function Tempest\Support\arr;
use function Tempest\Support\str;

final readonly class StaticMultipleChoiceComponent implements StaticConsoleComponent
{
    private OptionCollection $options;

    public function __construct(
        public string $label,
        iterable $options,
        public array $default = [],
    ) {
        $this->options = new OptionCollection($options);
    }

    public function render(Console $console): array
    {
        if (! $console->supportsPrompting()) {
            return $this->default;
        }

        do {
            $answers = $this->askQuestion($console);

            $answerAsString = arr($answers)
                ->map(fn (Option $option) => $option->displayValue)
                ->join(', ', ' and ')
                ->trim()
                ->toString() ?: 'no option';

            $continue = $console->confirm(
                question: $answers
                    ? "You picked {$answerAsString}; continue?"
                    : "Continue with {$answerAsString}?",
                default: true,
            );
        } while ($continue === false);

        if ($answers === []) {
            return $this->default;
        }

        return array_map(fn (Option $option) => $option->value, $answers);
    }

    private function askQuestion(Console $console): array
    {
        $console->writeln("<style='bold fg-blue'>{$this->label}</style> ");
        $console->write('<style="fg-gray">Select multiple items using comas</style>');

        $prompt = $this->options->getOptions()
            ->map(
                fn (Option $option, int $index) => str($index)
                        ->when(
                            condition: in_array($option->value, $this->default),
                            callback: fn ($s) => $s->wrap('<style="fg-blue">', '</style>'),
                        )
                        ->wrap('[', ']')
                        ->prepend('- ')
                        ->append(' ', $option->displayValue)
                        ->toString(),
            )
            ->implode(PHP_EOL)
            ->toString();

        $console->write(PHP_EOL . $prompt . PHP_EOL);

        return ArrayHelper::explode($console->readln(), separator: ',')
            ->map(function (string $answer) {
                $answer = trim($answer);

                return $this->options->getOptions()->first(function (Option $option, int $index) use ($answer) {
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
            })
            ->filter()
            ->unique(fn (Option $option) => $option->value)
            ->toArray();
    }
}
