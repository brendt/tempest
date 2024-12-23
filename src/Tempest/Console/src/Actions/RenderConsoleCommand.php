<?php

declare(strict_types=1);

namespace Tempest\Console\Actions;

use BackedEnum;
use Tempest\Console\Console;
use Tempest\Console\ConsoleCommand;
use Tempest\Console\Input\ConsoleArgumentDefinition;
use function Tempest\Support\str;

final readonly class RenderConsoleCommand
{
    public function __construct(
        private Console $console,
        private ?int $longestCommandName = null,
        private bool $renderArguments = false,
        private bool $renderDescription = true,
    ) {
    }

    public function __invoke(ConsoleCommand $consoleCommand): void
    {
        $parts = [$this->renderName($consoleCommand)];

        if ($this->renderArguments) {
            foreach ($consoleCommand->getArgumentDefinitions() as $argument) {
                $parts[] = '<style="fg-gray">' . $this->renderArgument($argument) . '</style>';
            }
        }

        if ($this->renderDescription) {
            if ($consoleCommand->description !== null && $consoleCommand->description !== '') {
                $parts[] = "<style='dim'>{$consoleCommand->description}</style>";
            }
        }

        $this->console->writeln(implode(' ', $parts));
    }

    private function renderName(ConsoleCommand $consoleCommand): string
    {
        return str($consoleCommand->getName())
            ->alignRight($this->longestCommandName, padding: $this->longestCommandName ? 2 : 0)
            ->toString();
    }

    private function renderArgument(ConsoleArgumentDefinition $argument): string
    {
        if ($argument->isBackedEnum()) {
            return $this->renderEnumArgument($argument);
        }

        $formattedArgumentName = match ($argument->type) {
            'bool' => "--{$argument->name}",
            default => $argument->name,
        };

        $formattedArgumentName = str($formattedArgumentName)->wrap('<style="fg-blue">', '</style>');

        if (! $argument->hasDefault) {
            return $formattedArgumentName->wrap('<style="fg-gray dim"><</style>', '<style="fg-gray dim">></style>')->toString();
        }

        $defaultValue = match (true) {
            $argument->default === true => 'true',
            $argument->default === false => 'false',
            is_null($argument->default) => 'null',
            is_array($argument->default) => 'array',
            default => "{$argument->default}",
        };

        return str()
            ->append(str('[')->wrap('<style="fg-gray dim">', '</style>'))
            ->append($formattedArgumentName)
            ->append(str('=')->wrap('<style="fg-gray dim">', '</style>'))
            ->append(str($defaultValue)->wrap('<style="fg-gray">', '</style>'))
            ->append(str(']')->wrap('<style="fg-gray dim">', '</style>'))
            ->toString();
    }

    private function renderEnumArgument(ConsoleArgumentDefinition $argument): string
    {
        $parts = array_map(
            callback: fn (BackedEnum $case) => $case->value,
            array: $argument->type::cases(),
        );

        $partsAsString = ' {<style="fg-blue">' . implode('|', $parts) . '</style>}';
        $line = "<style=\"fg-blue\">{$argument->name}</style>";

        if ($argument->hasDefault) {
            return "[{$line}={$argument->default->value}{$partsAsString}]";
        }

        return "<{$line}{$partsAsString}>";
    }
}
