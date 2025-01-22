<?php

declare(strict_types=1);

namespace Tempest\Console;

use Tempest\Console\Actions\ExecuteConsoleCommand;
use Tempest\Console\Input\ConsoleArgumentBag;
use Tempest\Container\Container;
use Tempest\Core\AppConfig;
use Tempest\Core\Application;
use Tempest\Core\Kernel;
use Tempest\Core\Tempest;
use Tempest\Log\Channels\AppendLogChannel;
use Tempest\Log\LogConfig;
use Throwable;
use function Tempest\path;

final readonly class ConsoleApplication implements Application
{
    public function __construct(
        private Container $container,
        private AppConfig $appConfig,
        private ConsoleArgumentBag $argumentBag,
    ) {
    }

    /** @param \Tempest\Discovery\DiscoveryLocation[] $discoveryLocations */
    public static function boot(
        string $name = 'Tempest',
        ?string $root = null,
        array $discoveryLocations = [],
    ): self {
        $container = Tempest::boot($root, $discoveryLocations);

        $application = $container->get(ConsoleApplication::class);

        // Application-specific config
        $consoleConfig = $container->get(ConsoleConfig::class);
        $consoleConfig->name = $name;

        $logConfig = $container->get(LogConfig::class);

        if (
            $logConfig->debugLogPath === null
            && $logConfig->channels === []
        ) {
            $logConfig->debugLogPath = path($container->get(Kernel::class)->root, '/log/debug.log')->toString();
            $logConfig->channels[] = new AppendLogChannel(path($container->get(Kernel::class)->root, '/log/tempest.log')->toString());
        }

        return $application;
    }

    public function run(): void
    {
        try {
            $exitCode = ($this->container->get(ExecuteConsoleCommand::class))($this->argumentBag->getCommandName());

            $exitCode = is_int($exitCode) ? $exitCode : $exitCode->value;

            if ($exitCode < 0 || $exitCode > 255) {
                throw new InvalidExitCode($exitCode);
            }

            $this->container->get(Kernel::class)->shutdown($exitCode);
        } catch (Throwable $throwable) {
            foreach ($this->appConfig->errorHandlers as $exceptionHandler) {
                $exceptionHandler->handleException($throwable);
            }

            throw $throwable;
        }
    }
}
