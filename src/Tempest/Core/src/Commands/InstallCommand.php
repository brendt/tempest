<?php

declare(strict_types=1);

namespace Tempest\Core\Commands;

use Tempest\Console\Console;
use Tempest\Console\ConsoleCommand;
use Tempest\Console\HasConsole;
use Tempest\Console\Middleware\ForceMiddleware;
use Tempest\Container\Container;
use Tempest\Core\Installer;
use Tempest\Core\InstallerConfig;
use Tempest\Core\Kernel\LoadDiscoveryClasses;
use function Tempest\Support\arr;

final readonly class InstallCommand
{
    use HasConsole;

    public function __construct(
        private InstallerConfig $installerConfig,
        private Console $console,
        private Container $container,
        private LoadDiscoveryClasses $loadDiscoveryClasses,
    ) {
    }

    #[ConsoleCommand(name: 'install', middleware: [ForceMiddleware::class])]
    public function __invoke(?string $installer = null): void
    {
        $installer = $this->resolveInstaller($installer);

        if (! $this->confirm("Running the `{$installer->getName()}` installer, continue?")) {
            $this->error('Aborted');

            return;
        }

        $installer->install();

        $this->success('Done');
    }

    private function resolveInstaller(?string $search): Installer
    {
        /** @var Installer[]|\Tempest\Support\ArrayHelper $installers */
        $installers = arr($this->installerConfig->installers)
            ->map(fn (string $installerClass) => $this->container->get($installerClass));

        if (! $search) {
            $search = $this->ask(
                question: 'Please choose an installer',
                options: $installers->map(fn (Installer $installer) => $installer->getName())->toArray(),
            );
        }

        return $installers->first(fn (Installer $installer) => $installer->getName() === $search);
    }
}
