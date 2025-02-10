<?php

declare(strict_types=1);

namespace Tempest\Console\Middleware;

use Tempest\Console\Actions\RenderConsoleCommand;
use Tempest\Console\Console;
use Tempest\Console\ConsoleCommand;
use Tempest\Console\ConsoleMiddleware;
use Tempest\Console\ConsoleMiddlewareCallable;
use Tempest\Console\ExitCode;
use Tempest\Console\Initializers\Invocation;

final readonly class HelpMiddleware implements ConsoleMiddleware
{
    public function __construct(
        private Console $console,
    ) {
    }

    public function __invoke(Invocation $invocation, ConsoleMiddlewareCallable $next): ExitCode|int
    {
        if ($invocation->argumentBag->get('-h') || $invocation->argumentBag->get('help')) {
            $this->renderHelp($invocation->consoleCommand);

            return ExitCode::SUCCESS;
        }

        return $next($invocation);
    }

    private function renderHelp(ConsoleCommand $consoleCommand): void
    {
        $this->console->header(
            header: $consoleCommand->getName(),
            subheader: $consoleCommand->description,
        );

        $this->console->header('Usage');
        new RenderConsoleCommand($this->console, renderArguments: true, renderDescription: false)($consoleCommand);

        if ($consoleCommand->help) {
            $this->console->writeln();
            $this->console->writeln('<style="fg-gray">' . $consoleCommand->help . '</style>');
        }

        foreach ($consoleCommand->getArgumentDefinitions() as $argumentDefinition) {
            if ($argumentDefinition->aliases === [] && ! $argumentDefinition->description && ! $argumentDefinition->help) {
                continue;
            }

            $this->console
                ->writeln()
                ->write("<style=\"underline\">{$argumentDefinition->name}</style>")
                ->when($argumentDefinition->aliases !== [], fn (Console $console) => $console->write(' (' . implode(', ', $argumentDefinition->aliases) . ')'))
                ->when($argumentDefinition->description, fn (Console $console) => $console->writeln()->writeln($argumentDefinition->description))
                ->when($argumentDefinition->help, fn (Console $console) => $console->writeln()->writeln('<style="fg-gray">' . $argumentDefinition->help . '</style>'));
        }

        $this->console->writeln();
    }
}
