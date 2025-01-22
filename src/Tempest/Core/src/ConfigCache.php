<?php

declare(strict_types=1);

namespace Tempest\Core;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Tempest\Cache\Cache;
use Tempest\Cache\IsCache;
use function Tempest\env;

final readonly class ConfigCache implements Cache
{
    use IsCache;

    private CacheItemPoolInterface $pool;

    public function __construct()
    {
        $this->pool = new FilesystemAdapter(
            directory: __DIR__ . '/.cache',
        );
    }

    protected function getCachePool(): CacheItemPoolInterface
    {
        return $this->pool;
    }

    public function isEnabled(): bool
    {
        return env('CACHE') ?? env('CONFIG_CACHE', false);
    }
}
