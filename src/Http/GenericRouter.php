<?php

declare(strict_types=1);

namespace Tempest\Http;

use Closure;
use ReflectionClass;
use Tempest\AppConfig;
use function Tempest\attribute;
use Tempest\Container\Container;
use Tempest\Container\InitializedBy;
use Tempest\Http\Exceptions\InvalidRouteException;
use Tempest\Http\Exceptions\MissingControllerOutputException;
use function Tempest\response;
use Tempest\View\View;

#[InitializedBy(RouteInitializer::class)]
final readonly class GenericRouter implements Router
{
    public function __construct(
        private Container $container,
        private AppConfig $appConfig,
        private RouteConfig $routeConfig,
    ) {
    }

    /**
     * @return \Tempest\Http\Route[][]
     */
    public function getRoutes(): array
    {
        return $this->routeConfig->routes;
    }

    public function dispatch(Request $request): Response
    {
        $matchedRoute = $this->matchRoute($request);

        if ($matchedRoute === null) {
            return response()->notFound();
        }

        $this->container->singleton(
            MatchedRoute::class,
            fn () => $matchedRoute,
        );

        $callable = $this->getCallable($matchedRoute);

        $outputFromController = $callable($request);

        if ($outputFromController === null) {
            throw new MissingControllerOutputException(
                $matchedRoute->route->handler->getDeclaringClass()->getName(),
                $matchedRoute->route->handler->getName(),
            );
        }

        return $this->createResponse($outputFromController);
    }

    public function matchRoute(Request $request): ?MatchedRoute
    {
        $actionsForMethod = $this->getRoutes()[$request->method->value] ?? null;

        if ($actionsForMethod === null) {
            return null;
        }

        foreach ($actionsForMethod as $route) {
            $routeParams = $this->resolveParams($route->uri, $request->getPath());

            if ($routeParams !== null) {
                return new MatchedRoute($route, $routeParams);
            }
        }

        return null;
    }

    private function getCallable(MatchedRoute $matchedRoute): Closure
    {
        $route = $matchedRoute->route;

        $callable = fn (Request $request) => $this->container->call(
            $this->container->get($route->handler->getDeclaringClass()->getName()),
            $route->handler->getName(),
            ...$matchedRoute->params,
        );

        $middlewareStack = $route->middleware;

        while ($middlewareClass = array_pop($middlewareStack)) {
            $callable = fn (Request $request) => $this->container->get($middlewareClass)($request, $callable);
        }

        return $callable;
    }

    public function toUri(array|string $action, ...$params): string
    {
        if (is_array($action)) {
            $controllerClass = $action[0];
            $reflection = new ReflectionClass($controllerClass);
            $controllerMethod = $reflection->getMethod($action[1]);
        } else {
            $controllerClass = $action;
            $reflection = new ReflectionClass($controllerClass);
            $controllerMethod = $reflection->getMethod('__invoke');
        }

        $routeAttribute = attribute(Route::class)->in($controllerMethod)->first();

        if (! $routeAttribute) {
            throw new InvalidRouteException($controllerClass, $controllerMethod->getName());
        }

        $uri = $routeAttribute->uri;

        foreach ($params as $key => $value) {
            $uri = str_replace('{' . $key . '}', "{$value}", $uri);
        }

        return $uri;
    }

    private function resolveParams(string $pattern, string $uri): ?array
    {
        if ($pattern === $uri) {
            return [];
        }

        $result = preg_match_all('/\{\w+}/', $pattern, $tokens);

        if (! $result) {
            return null;
        }

        $tokens = $tokens[0];

        $matchingRegex = '/^' . str_replace(
            ['/', ...$tokens],
            ['\\/', ...array_fill(0, count($tokens), '([\w\d\s]+)')],
            $pattern,
        ) . '$/';

        $result = preg_match_all($matchingRegex, $uri, $matches);

        if ($result === 0) {
            return null;
        }

        unset($matches[0]);

        $matches = array_values($matches);

        $valueMap = [];

        foreach ($matches as $i => $match) {
            $valueMap[trim($tokens[$i], '{}')] = $match[0];
        }

        return $valueMap;
    }

    private function createResponse(Response|View $input): Response
    {
        if ($input instanceof View) {
            return response($input->render($this->appConfig));
        }

        return $input;
    }
}
