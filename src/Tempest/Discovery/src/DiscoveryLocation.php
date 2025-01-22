<?php

declare(strict_types=1);

namespace Tempest\Discovery;

final readonly class DiscoveryLocation
{
    public function __construct(
        public string $namespace,
        public string $path,
    ) {
    }

    public function isVendor(): bool
    {
        return str_contains($this->path, '/vendor/')
            || str_contains($this->path, '\\vendor\\');
    }

    public function toClassName(string $path): string
    {
        $pathWithoutSlashes = rtrim($this->path, '\\/');

        // Try to create a PSR-compliant class name from the path
        return str_replace(
            [
                $pathWithoutSlashes,
                '/',
                '\\\\',
                '.php',
            ],
            [
                $this->namespace,
                '\\',
                '\\',
                '',
            ],
            $path,
        );
    }
}
