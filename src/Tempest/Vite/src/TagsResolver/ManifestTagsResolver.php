<?php

declare(strict_types=1);

namespace Tempest\Vite\TagsResolver;

use Tempest\Vite\Exceptions\EntrypointNotFoundException;
use Tempest\Vite\Manifest\Chunk;
use Tempest\Vite\Manifest\Manifest;
use Tempest\Vite\PrefetchStrategy;
use Tempest\Vite\TagCompiler\TagCompiler;
use Tempest\Vite\ViteConfig;
use function Tempest\root_path;
use function Tempest\Support\arr;
use function Tempest\Support\str;

final readonly class ManifestTagsResolver implements TagsResolver
{
    public function __construct(
        private ViteConfig $viteConfig,
        private TagCompiler $tagCompiler,
        private Manifest $manifest,
    ) {
    }

    public function resolveTags(array $entrypoints): array
    {
        return arr($entrypoints)
            ->flatMap(function (string $entrypoint) {
                $path = $this->fileToAssetPath($entrypoint);

                if (! $chunk = $this->manifest->chunks->get($path)) {
                    throw new EntrypointNotFoundException($entrypoint);
                }

                return $this->resolveEntryPointTags($chunk);
            })
            ->toArray();
    }

    private function resolveEntryPointTags(Chunk $entrypoint): array
    {
        return arr()
            ->append(...$this->getPreloadTags($entrypoint))
            ->append(...$this->getStyleTags($entrypoint))
            ->append($this->resolveChunkTag($entrypoint))
            ->append($this->resolvePrefetchingScript($entrypoint))
            ->unique()
            ->filter()
            ->toArray();
    }

    private function resolveChunkTag(Chunk $chunk): string
    {
        if (str_ends_with($chunk->file, '.css')) {
            return $this->tagCompiler->compileStyleTag($this->getChunkPath($chunk), $chunk);
        }

        if ($chunk->isEntry) {
            return $this->tagCompiler->compileScriptTag($this->getChunkPath($chunk), $chunk);
        }

        return $this->tagCompiler->compilePreloadTag($this->getChunkPath($chunk), $chunk);
    }

    private function getStyleTags(Chunk $chunk): array
    {
        $seenFiles = [];

        $getStyleChunks = function (Chunk $chunk) use (&$seenFiles, &$getStyleChunks) {
            $styleChunks = [];

            foreach ($chunk->imports as $importFile) {
                if (isset($seenFiles[$importFile])) {
                    continue;
                }

                $seenFiles[$importFile] = true;
                $importChunk = $this->manifest->chunks[$importFile] ?? null;

                if ($importChunk) {
                    $styleChunks = array_merge(
                        $styleChunks,
                        $getStyleChunks($importChunk),
                    );
                }
            }

            return array_merge(
                $styleChunks,
                array_map(fn (string $path) => ['file' => $path], $chunk->css),
            );
        };

        $styleChunks = $getStyleChunks($chunk);

        return arr($styleChunks)
            ->map(fn (array $styleChunk) => $this->tagCompiler->compileStyleTag($this->getAssetPath($styleChunk['file'])))
            ->unique()
            ->toArray();
    }

    private function getPreloadTags(Chunk $chunk): array
    {
        $seenFiles = [];
        $findPreloadableChunks = function (Chunk $chunk) use (&$seenFiles, &$findPreloadableChunks) {
            $preloadChunks = [];

            foreach ($chunk->imports as $importFile) {
                if (isset($seenFiles[$importFile])) {
                    continue;
                }

                $seenFiles[$importFile] = true;

                if ($importChunk = $this->manifest->chunks[$importFile] ?? null) {
                    $preloadChunks = [
                        ...$preloadChunks,
                        ...$findPreloadableChunks($importChunk),
                        $importChunk,
                    ];
                }
            }

            return $preloadChunks;
        };

        $preloadChunks = $findPreloadableChunks($chunk);

        return arr($preloadChunks)
            ->map(fn (Chunk $preloadChunk) => $this->tagCompiler->compilePreloadTag($this->getChunkPath($preloadChunk), $preloadChunk))
            ->unique()
            ->toArray();
    }

    private function resolvePrefetchingScript(Chunk $chunk): ?string
    {
        if ($this->viteConfig->prefetching->strategy === PrefetchStrategy::NONE) {
            return null;
        }

        $seenFiles = [];
        $findPrefetchableAssets = function (Chunk $chunk) use (&$seenFiles, &$findPrefetchableAssets) {
            $assets = [];
            $importsToProcess = array_merge($chunk->imports, $chunk->dynamicImports);

            foreach ($importsToProcess as $importFile) {
                if (isset($seenFiles[$importFile])) {
                    continue;
                }

                $seenFiles[$importFile] = true;

                if ($importChunk = $this->manifest->chunks[$importFile] ?? null) {
                    $assets = array_merge($assets, $findPrefetchableAssets($importChunk));

                    foreach ($importChunk->css as $cssFile) {
                        $assets[] = [
                            'rel' => 'prefetch',
                            'fetchpriority' => 'low',
                            'href' => $this->getAssetPath($cssFile),
                        ];
                    }

                    if (str_ends_with($importChunk->file, '.js')) {
                        $assets[] = [
                            'rel' => 'prefetch',
                            'fetchpriority' => 'low',
                            'href' => $this->getAssetPath($importChunk->file),
                        ];
                    }
                }
            }

            return $assets;
        };

        $assets = json_encode(array_values(array_map(
            callback: fn (array $asset) => array_map('strval', $asset),
            array: array_unique($findPrefetchableAssets($chunk), flags: SORT_REGULAR),
        )));

        $script = match ($this->viteConfig->prefetching->strategy) {
            PrefetchStrategy::AGGRESSIVE => <<<JS
                    window.addEventListener('{$this->viteConfig->prefetching->prefetchEvent}', () => window.setTimeout(() => {
                        function makeLink(asset) {
                            const link = document.createElement('link')
                            Object.keys(asset).forEach((attribute) => link.setAttribute(attribute, asset[attribute]))
                            return link
                        }

                        const fragment = new DocumentFragment()
                        {$assets}.forEach((asset) => fragment.append(makeLink(asset)))
                        document.head.append(fragment)
                    }))
                JS,
            PrefetchStrategy::WATERFALL => <<<JS
                    window.addEventListener('{$this->viteConfig->prefetching->prefetchEvent}', () => {
                        function makeLink(asset) {
                            const link = document.createElement('link')
                            Object.entries(asset).forEach(([key, value]) => link.setAttribute(key, value))
                            return link
                        }

                        function loadNext(assets, count) {
                            if (!assets.length) return

                            const fragment = new DocumentFragment()
                            const limit = Math.min(count, assets.length)

                            for (let i = 0; i < limit; i++) {
                                const link = makeLink(assets.shift())
                                fragment.append(link)

                                if (assets.length) {
                                    link.onload = () => loadNext(assets, 1)
                                    link.onerror = () => loadNext(assets, 1)
                                }
                            }

                            document.head.append(fragment)
                        }

                        setTimeout(() => loadNext({$assets}, {$this->viteConfig->prefetching->concurrent}))
                    })
                JS,
        };

        return $this->tagCompiler->compilePrefetchTag($script, $chunk);
    }

    private function getChunkPath(Chunk $chunk): string
    {
        return $this->getAssetPath($chunk->file);
    }

    private function getAssetPath(string $path): string
    {
        return $this->viteConfig->build->buildDirectory . '/' . $path;
    }

    private function fileToAssetPath(string $file): string
    {
        return str($file)
            ->when(
                condition: fn ($file) => $file->startsWith('./'),
                callback: fn ($file) => str(realpath(root_path($file->toString()))),
            )
            ->replaceStart(root_path('public'), '')
            ->replaceStart(root_path(), '')
            ->replaceStart('/', '')
            ->toString();
    }
}
