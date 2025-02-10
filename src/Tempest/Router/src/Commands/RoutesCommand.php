<?php

declare(strict_types=1);

namespace Tempest\Router\Commands;

use Tempest\Console\Console;
use Tempest\Console\ConsoleArgument;
use Tempest\Console\ConsoleCommand;
use Tempest\Http\Method;
use Tempest\Router\RouteConfig;
use function Tempest\Support\str;

final readonly class RoutesCommand
{
    public function __construct(
        private Console $console,
        private RouteConfig $routeConfig,
    ) {
    }

    #[ConsoleCommand(name: 'routes', description: 'Lists all registered routes', aliases: ['routes:list', 'list:routes'])]
    public function list(
        #[ConsoleArgument(description: 'Outputs registered routes as JSON')]
        bool $json = false,
    ): void {
        $sortedRoutes = [];

        foreach ($this->routeConfig->dynamicRoutes as $method => $routesForMethod) {
            foreach ($routesForMethod as $route) {
                $sortedRoutes["{$route->uri}:{$method}"] = $route;
            }
        }

        foreach ($this->routeConfig->staticRoutes as $method => $routesForMethod) {
            foreach ($routesForMethod as $route) {
                $sortedRoutes["{$route->uri}:{$method}"] = $route;
            }
        }

        ksort($sortedRoutes);

        if ($json) {
            $this->console->writeRaw(json_encode($sortedRoutes, flags: JSON_UNESCAPED_UNICODE));

            return;
        }

        $this->console->header('Registered routes', subheader: 'These routes are registered in your application.');
        $this->console->writeln();

        foreach ($sortedRoutes as $route) {
            $color = match ($route->method) {
                Method::GET => 'magenta',
                Method::POST => 'yellow',
                Method::PUT => 'green',
                Method::PATCH => 'blue',
                Method::DELETE => 'red',
                default => 'gray',
            };

            $this->console->keyValue(
                key: str($route->method->value)
                    ->alignLeft(width: 5)
                    ->wrap("<style='fg-{$color}'>", '</style>')
                    ->append($route->uri)
                    ->toString(),
                value: str()
                    ->append("<style='dim'>{$route->handler->getDeclaringClass()->getName()}::{$route->handler->getName()}</style>")
                    ->toString(),
            );
        }
    }
}
