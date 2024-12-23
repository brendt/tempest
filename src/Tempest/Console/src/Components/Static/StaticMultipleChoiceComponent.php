<?php

declare(strict_types=1);

namespace Tempest\Console\Components\Static;

use Tempest\Console\Console;
use Tempest\Console\StaticConsoleComponent;
use function Tempest\Support\arr;

final readonly class StaticMultipleChoiceComponent implements StaticConsoleComponent
{
    public function __construct(
        public string $label,
        public array $options,
        public array $default = [],
    ) {
    }

    public function render(Console $console): array
    {
        if (! $console->supportsPrompting()) {
            return array_is_list($this->options)
                ? array_filter($this->default, fn (mixed $value) => in_array($value, $this->options))
                : array_filter($this->default, fn (mixed $value) => array_key_exists($value, $this->options));
        }

        do {
            $answer = $this->askQuestion($console);

            $answerAsString = arr($answer)->join(', ', ' and ')->trim()->toString() ?: 'no option';

            $continue = $console->confirm(
                question: $answer
                    ? "You picked {$answerAsString}; continue?"
                    : "Continue with {$answerAsString}?",
                default: true,
            );
        } while ($continue === false);

        return $answer ?: $this->default;
    }

    private function askQuestion(Console $console): array
    {
        $console->writeln("<style='bold fg-blue'>{$this->label}</style> ");
        $console->write('<style="fg-gray">Select multiple items using comas</style>');

        $parsedOptions = [];

        foreach ($this->options as $key => $option) {
            $parsedOptions[$key] = "- [{$key}] {$option}";
        }

        $console->write(PHP_EOL . implode(PHP_EOL, $parsedOptions) . PHP_EOL);

        $answers = explode(',', $console->readln());

        $validAnswers = [];

        foreach ($answers as $answer) {
            $answer = trim($answer);

            if (! array_key_exists($answer, $this->options)) {
                continue;
            }

            $validAnswers[] = $this->options[$answer];
        }

        return $validAnswers;
    }
}
