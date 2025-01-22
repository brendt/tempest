<?php

declare(strict_types=1);

namespace Tempest\Router;

use Tempest\Http\Method;
use Tempest\Router\Cookie\Cookie;
use Tempest\Router\Cookie\CookieManager;
use Tempest\Router\Session\Session;
use function Tempest\get;

/** @phpstan-require-implements \Tempest\Router\Request */
trait IsRequest
{
    private(set) Method $method;

    private(set) string $uri;

    private(set) array $body = [];

    private(set) array $headers = [];

    private(set) string $path;

    private(set) array $query;

    /** @var \Tempest\Router\Upload[] */
    private(set) array $files;

    public array $cookies {
        get => get(CookieManager::class)->all();
    }

    public function __construct(
        Method $method,
        string $uri,
        array $body = [],
        array $headers = [],
        array $files = [],
    ) {
        $this->method = $method;
        $this->uri = $uri;
        $this->body = $body;
        $this->headers = $headers;
        $this->files = $files;

        $this->path ??= $this->resolvePath();
        $this->query ??= $this->resolveQuery();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->body)) {
            return $this->body[$key];
        }

        if (array_key_exists($key, $this->query)) {
            return $this->query[$key];
        }

        return $default;
    }

    public function getSessionValue(string $name): mixed
    {
        /** @var Session $session */
        $session = get(Session::class);

        return $session->get($name);
    }

    public function getCookie(string $name): ?Cookie
    {
        /** @var CookieManager $cookies */
        $cookies = get(CookieManager::class);

        return $cookies->get($name);
    }

    public function validate(): void
    {
        // No additional validation done
    }

    private function resolvePath(): string
    {
        $decodedUri = rawurldecode($this->uri);
        $parsedUrl = parse_url($decodedUri);

        return $parsedUrl['path'] ?? '';
    }

    private function resolveQuery(): array
    {
        $decodedUri = rawurldecode($this->uri);
        $parsedUrl = parse_url($decodedUri);
        $queryString = $parsedUrl['query'] ?? '';

        parse_str($queryString, $query);

        return $query;
    }

    public function has(string $key): bool
    {
        if ($this->hasBody($key)) {
            return true;
        }

        return $this->hasQuery($key);
    }

    public function hasBody(string $key): bool
    {
        return array_key_exists($key, $this->body);
    }

    public function hasQuery(string $key): bool
    {
        return array_key_exists($key, $this->query);
    }
}
