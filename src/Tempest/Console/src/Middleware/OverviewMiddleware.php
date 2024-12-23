<?php

declare(strict_types=1);

namespace Tempest\Console\Middleware;

use Tempest\Console\Actions\RenderConsoleCommand;
use Tempest\Console\Console;
use Tempest\Console\ConsoleCommand;
use Tempest\Console\ConsoleConfig;
use Tempest\Console\ConsoleMiddleware;
use Tempest\Console\ConsoleMiddlewareCallable;
use Tempest\Console\ExitCode;
use Tempest\Console\Initializers\Invocation;
use Tempest\Core\DiscoveryCache;
use function Tempest\Support\arr;
use function Tempest\Support\str;

final readonly class OverviewMiddleware implements ConsoleMiddleware
{
    public function __construct(
        private Console $console,
        private ConsoleConfig $consoleConfig,
        private DiscoveryCache $discoveryCache,
    ) {
    }

    public function __invoke(Invocation $invocation, ConsoleMiddlewareCallable $next): ExitCode|int
    {
        if (! $invocation->argumentBag->getCommandName()) {
            $this->renderOverview(showHidden: $invocation->argumentBag->has('--all', '-a'));

            return ExitCode::SUCCESS;
        }

        return $next($invocation);
    }

    private function renderOverview(bool $showHidden = false): void
    {
        $this->console->header(
            header: $this->consoleConfig->name,
            subheader: 'This is an overview of available commands.' . PHP_EOL . 'Type <em><command> --help</em> to get more help about a specific command.',
        );

        if ($this->discoveryCache->isEnabled()) {
            $this->console->writeln();
            $this->console->error('<style="bold">Caution</style>: discovery cache is enabled');
        }

        $this->console->writeln();

        /** @var \Tempest\Console\ConsoleCommand[][] $commands */
        $commands = [];

        foreach ($this->consoleConfig->commands as $consoleCommand) {
            if ($showHidden === false && $consoleCommand->hidden) {
                continue;
            }

            $parts = explode(':', $consoleCommand->getName());

            $group = count($parts) > 1 ? $parts[0] : 'General';

            $commands[$group][$consoleCommand->getName()] = $consoleCommand;
        }

        ksort($commands);

        $longestCommandName = max(
            arr($commands)
                ->flatMap(fn (array $group) => $group)
                ->map(fn (ConsoleCommand $command) => mb_strlen($command->getName()))
                ->toArray(),
        ) + 4;

        foreach ($commands as $group => $commandsForGroup) {
            $title = str(mb_strtoupper($group))
                ->alignRight($longestCommandName, padding: 5)
                ->replaceRegex('/^( *)(.*?)( *)$/', "$1<style='dim fg-blue'>//</style> <style='bold fg-blue'>$2</style>$3")
                ->toString();

            $this->console
                ->writeln()
                ->writeln($title);

            foreach ($commandsForGroup as $consoleCommand) {
                new RenderConsoleCommand($this->console, $longestCommandName)($consoleCommand);
            }
        }

        $this->console
            ->unless(
                condition: $this->discoveryCache->isValid(),
                callback: fn (Console $console) => $console->writeln()->error('Discovery cache invalid. Run discovery:generate to enable discovery caching.'),
            );
    }
}
