<?php

declare(strict_types=1);

namespace Tempest\Console\Components\Static;

use Tempest\Console\Console;
use Tempest\Console\StaticConsoleComponent;

final readonly class StaticSingleChoiceComponent implements StaticConsoleComponent
{
    private array $optionValues;

    public function __construct(
        public string $label,
        public array $options,
        public mixed $default = null,
    ) {
        $this->optionValues = array_values($options);
    }

    public function render(Console $console): null|string|int
    {
        if (! $console->supportsPrompting()) {
            return $this->default;
        }

        $console->write("<style='bold fg-blue'>{$this->label}</style> ");

        $parsedOptions = [];

        foreach ($this->optionValues as $key => $option) {
            $key = $key === $this->default
                ? "<em><strong>{$key}</strong></em>"
                : $key;

            $parsedOptions[$key] = "- [{$key}] {$option}";
        }

        $console->write(PHP_EOL . implode(PHP_EOL, $parsedOptions) . PHP_EOL);

        $answer = trim($console->readln());

        if (! $answer && $this->default) {
            return $this->default;
        }

        if (array_key_exists($answer, $this->optionValues)) {
            return array_is_list($this->options)
                ? $this->optionValues[$answer]
                : array_search($this->optionValues[$answer], $this->options, strict: false);
        }

        if (in_array($answer, $this->optionValues, strict: false)) {
            return array_is_list($this->options)
                ? $answer
                : array_search($answer, $this->options, strict: false);
        }

        return $this->render($console);
    }
}
