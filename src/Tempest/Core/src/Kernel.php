<?php

declare(strict_types=1);

namespace Tempest\Core;

use Dotenv\Dotenv;
use Tempest\Console\Exceptions\ConsoleErrorHandler;
use Tempest\Container\Container;
use Tempest\Container\GenericContainer;
use Tempest\Core\Kernel\FinishDeferredTasks;
use Tempest\Core\Kernel\LoadConfig;
use Tempest\Core\Kernel\LoadDiscoveryClasses;
use Tempest\Core\Kernel\LoadDiscoveryLocations;
use Tempest\Core\ShellExecutors\GenericShellExecutor;
use Tempest\EventBus\EventBus;
use Tempest\Router\Exceptions\HttpProductionErrorHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

final class Kernel
{
    public readonly Container $container;

    public bool $discoveryCache;

    public array $discoveryClasses = [];

    public function __construct(
        public string $root,
        /** @var \Tempest\Discovery\DiscoveryLocation[] $discoveryLocations */
        public array $discoveryLocations = [],
        ?Container $container = null,
    ) {
        $this->container = $container ?? $this->createContainer();
    }

    public static function boot(
        string $root,
        array $discoveryLocations = [],
        ?Container $container = null,
    ): self {
        if (! defined('TEMPEST_START')) {
            define('TEMPEST_START', value: hrtime(true));
        }

        return new self(
            root: $root,
            discoveryLocations: $discoveryLocations,
            container: $container,
        )
            ->loadEnv()
            ->registerKernelErrorHandler()
            ->registerShutdownFunction()
            ->registerKernel()
            ->loadComposer()
            ->loadDiscoveryLocations()
            ->loadConfig()
            ->loadDiscovery()
            ->registerErrorHandler()
            ->event(KernelEvent::BOOTED);
    }

    public function shutdown(int|string $status = ''): never
    {
        $this
            ->finishDeferredTasks()
            ->event(KernelEvent::SHUTDOWN);

        exit($status);
    }

    public function createContainer(): Container
    {
        $container = new GenericContainer();

        GenericContainer::setInstance($container);

        $container->singleton(Container::class, fn () => $container);

        return $container;
    }

    public function loadComposer(): self
    {
        $composer = new Composer(
            root: $this->root,
            executor: new GenericShellExecutor(),
        )->load();

        $this->container->singleton(Composer::class, $composer);

        return $this;
    }

    public function loadEnv(): self
    {
        $dotenv = Dotenv::createUnsafeImmutable($this->root);
        $dotenv->safeLoad();

        return $this;
    }

    public function registerKernel(): self
    {
        $this->container->singleton(self::class, $this);

        return $this;
    }

    public function registerShutdownFunction(): self
    {
        // Fix for classes that don't have a proper PSR-4 namespace,
        // they break discovery with an unrecoverable error,
        // but you don't know why because PHP simply says "duplicate classname" instead of something reasonable.
        register_shutdown_function(function (): void {
            $error = error_get_last();

            $message = $error['message'] ?? '';

            if (str_contains($message, 'Cannot declare class')) {
                echo 'Does this class have the right namespace?' . PHP_EOL;
            }
        });

        return $this;
    }

    public function loadDiscoveryLocations(): self
    {
        $this->container->invoke(LoadDiscoveryLocations::class);

        return $this;
    }

    public function loadDiscovery(): self
    {
        $this->container->invoke(LoadDiscoveryClasses::class);

        return $this;
    }

    public function loadConfig(): self
    {
        $this->container->invoke(LoadConfig::class);

        return $this;
    }

    public function registerErrorHandler(): self
    {
        $appConfig = $this->container->get(AppConfig::class);

        if ($appConfig->environment->isTesting()) {
            return $this;
        }

        if (PHP_SAPI === 'cli') {
            $handler = $this->container->get(ConsoleErrorHandler::class);
            set_exception_handler($handler->handleException(...));
            set_error_handler($handler->handleError(...)); // @phpstan-ignore-line
        } elseif ($appConfig->environment->isProduction()) {
            $handler = $this->container->get(HttpProductionErrorHandler::class);
            set_exception_handler($handler->handleException(...));
            set_error_handler($handler->handleError(...)); // @phpstan-ignore-line
        }

        return $this;
    }

    public function finishDeferredTasks(): self
    {
        $this->container->invoke(FinishDeferredTasks::class);

        return $this;
    }

    public function event(object $event): self
    {
        if (interface_exists(EventBus::class)) {
            $this->container->get(EventBus::class)->dispatch($event);
        }

        return $this;
    }

    public function registerKernelErrorHandler(): self
    {
        $environment = Environment::fromEnv();

        if ($environment->isTesting()) {
            return $this;
        }

        if (PHP_SAPI !== 'cli' && $environment->isProduction()) {
            $handler = new HttpProductionErrorHandler();
            set_exception_handler($handler->handleException(...));
            set_error_handler($handler->handleError(...)); // @phpstan-ignore-line
        } elseif (PHP_SAPI !== 'cli') {
            $whoops = new Run();
            $whoops->pushHandler(new PrettyPageHandler());
            $whoops->register();
        }

        return $this;
    }
}
