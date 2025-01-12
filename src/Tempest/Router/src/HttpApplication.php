<?php

declare(strict_types=1);

namespace Tempest\Router;

use Tempest\Container\Container;
use Tempest\Container\Singleton;
use Tempest\Core\AppConfig;
use Tempest\Core\Application;
use Tempest\Core\Kernel;
use Tempest\Core\Tempest;
use Tempest\Log\Channels\AppendLogChannel;
use Tempest\Log\LogConfig;
use Tempest\Router\Session\Session;
use Throwable;
use function Tempest\env;
use function Tempest\path;

#[Singleton]
final readonly class HttpApplication implements Application
{
    public function __construct(private Container $container)
    {
    }

    /** @param \Tempest\Discovery\DiscoveryLocation[] $discoveryLocations */
    public static function boot(
        string $root,
        array $discoveryLocations = [],
    ): self {
        $container = Tempest::boot($root, $discoveryLocations);

        $application = $container->get(HttpApplication::class);

        // Application-specific setup
        $logConfig = $container->get(LogConfig::class);

        if (
            $logConfig->debugLogPath === null
            && $logConfig->serverLogPath === null
            && $logConfig->channels === []
        ) {
            $logConfig->debugLogPath = path($container->get(Kernel::class)->root, '/log/debug.log')->toString();
            $logConfig->serverLogPath = env('SERVER_LOG');
            $logConfig->channels[] = new AppendLogChannel(path($root, '/log/tempest.log')->toString());
        }

        return $application;
    }

    public function run(): void
    {
        try {
            $router = $this->container->get(Router::class);

            $psrRequest = new RequestFactory()->make();

            $responseSender = $this->container->get(ResponseSender::class);

            $responseSender->send(
                $router->dispatch($psrRequest),
            );

            $this->container->get(Session::class)->cleanup();

            $this->container->get(Kernel::class)->shutdown();
        } catch (Throwable $throwable) {
            foreach ($this->container->get(AppConfig::class)->errorHandlers as $exceptionHandler) {
                $exceptionHandler->handleException($throwable);
            }

            throw $throwable;
        }
    }
}
