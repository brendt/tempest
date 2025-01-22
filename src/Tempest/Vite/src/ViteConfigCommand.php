<?php

declare(strict_types=1);

namespace Tempest\Vite;

use Tempest\Console\Console;
use Tempest\Console\ConsoleCommand;

final readonly class ViteConfigCommand
{
    public function __construct(
        private Console $console,
        private ViteConfig $viteConfig,
    ) {
    }

    #[ConsoleCommand(name: 'vite:config', hidden: true)]
    public function __invoke(): void
    {
        $this->console->writeRaw(json_encode([
            'build_directory' => $this->viteConfig->build->buildDirectory,
            'bridge_file_name' => $this->viteConfig->build->bridgeFileName,
            'manifest' => $this->viteConfig->build->manifest,
            'entrypoints' => $this->viteConfig->build->entrypoints,
        ]));
    }
}
