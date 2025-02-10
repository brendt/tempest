<?php

declare(strict_types=1);

namespace Tempest\Console\Middleware;

use Stringable;
use Tempest\Console\Actions\ExecuteConsoleCommand;
use Tempest\Console\Actions\ResolveConsoleCommand;
use Tempest\Console\Console;
use Tempest\Console\ConsoleConfig;
use Tempest\Console\ConsoleMiddleware;
use Tempest\Console\ConsoleMiddlewareCallable;
use Tempest\Console\ExitCode;
use Tempest\Console\Initializers\Invocation;
use Tempest\Support\ArrayHelper;
use Tempest\Support\StringHelper;
use Throwable;
use function Tempest\Support\arr;
use function Tempest\Support\str;

final readonly class ResolveOrRescueMiddleware implements ConsoleMiddleware
{
    public function __construct(
        private ConsoleConfig $consoleConfig,
        private Console $console,
        private ExecuteConsoleCommand $executeConsoleCommand,
        private ResolveConsoleCommand $resolveConsoleCommand,
    ) {
    }

    public function __invoke(Invocation $invocation, ConsoleMiddlewareCallable $next): ExitCode|int
    {
        try {
            $consoleCommand = ($this->resolveConsoleCommand)($invocation->argumentBag->getCommandName());
        } catch (Throwable) {
            return $this->rescue($invocation->argumentBag->getCommandName());
        }

        return $next(new Invocation(
            argumentBag: $invocation->argumentBag,
            consoleCommand: $consoleCommand,
        ));
    }

    private function rescue(string $commandName): ExitCode|int
    {
        $this->console->writeln();
        $this->console->writeln('<style="bg-dark-red fg-white"> Error </style>');
        $this->console->writeln("<style='fg-red'>Command <em>{$commandName}</em> not found.</style>");

        $similarCommands = $this->getSimilarCommands(str($commandName));

        if ($similarCommands->isEmpty()) {
            return ExitCode::ERROR;
        }

        if ($similarCommands->count() === 1) {
            $matchedCommand = $similarCommands->first();

            if ($this->console->confirm("Did you mean <em>{$matchedCommand}</em>?", default: true)) {
                return $this->runIntendedCommand($matchedCommand);
            }

            return ExitCode::CANCELLED;
        }

        $intendedCommand = $this->console->ask(
            'Did you mean to run one of these?',
            options: $similarCommands,
        );

        return $this->runIntendedCommand($intendedCommand);
    }

    private function getSimilarCommands(StringHelper $search): ArrayHelper
    {
        /** @var ArrayHelper<array-key, StringHelper> $suggestions */
        $suggestions = arr();

        foreach ($this->consoleConfig->commands as $consoleCommand) {
            $currentName = str($consoleCommand->getName());

            // Already added to suggestions
            if ($suggestions->contains($currentName->toString())) {
                continue;
            }

            $currentParts = $currentName->explode(':');
            $searchParts = $search->explode(':');

            // `dis:st` will match `discovery:status`
            if ($searchParts->count() === $currentParts->count()) {
                if ($searchParts->every(fn (string $part, int $index) => str_starts_with($currentParts[$index], $part))) {
                    $suggestions[] = $currentName;

                    continue;
                }
            }

            // `generate` will match `discovery:generate`
            if ($currentName->startsWith($search) || $currentName->endsWith($search)) {
                $suggestions[] = $currentName;

                continue;
            }

            // Match with levenshtein on the whole command
            if ($currentName->levenshtein($search) <= 2) {
                $suggestions[] = $currentName;

                continue;
            }

            // Match with levenshtein on each command part
            foreach ($currentParts as $part) {
                $part = str($part);

                // `clean` will match `static:clean` but also `discovery:clear`
                if ($part->levenshtein($search) <= 1) {
                    $suggestions[] = $currentName;

                    continue 2;
                }

                // `generate` will match `discovery:generate`
                if ($part->startsWith($search)) {
                    $suggestions[] = $currentName;

                    continue 2;
                }
            }
        }

        // Sort with levenshtein
        $sorted = arr();

        foreach ($suggestions as $suggestion) {
            // Calculate the levenshtein difference on the whole suggestion
            $levenshtein = $suggestion->levenshtein($search);

            // Calculate the levenshtein difference on each part of the suggestion
            foreach ($suggestion->explode(':') as $suggestionPart) {
                // Always use the closest distance
                $levenshtein = min($levenshtein, str($suggestionPart)->levenshtein($search));
            }

            $sorted[] = ['levenshtein' => $levenshtein, 'suggestion' => $suggestion];
        }

        return $sorted
            ->sortByCallback(fn (array $a, array $b) => $a['levenshtein'] <=> $b['levenshtein'])
            ->map(fn (array $item) => $item['suggestion']);
    }

    private function runIntendedCommand(Stringable $commandName): ExitCode|int
    {
        return ($this->executeConsoleCommand)((string) $commandName);
    }
}
