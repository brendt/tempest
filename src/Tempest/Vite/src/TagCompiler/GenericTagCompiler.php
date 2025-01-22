<?php

declare(strict_types=1);

namespace Tempest\Vite\TagCompiler;

use Tempest\Support\HtmlString;
use Tempest\Vite\Manifest\Chunk;
use Tempest\Vite\ViteConfig;

final readonly class GenericTagCompiler implements TagCompiler
{
    public function __construct(
        private ViteConfig $viteConfig,
    ) {
    }

    public function compileScriptTag(string $url, ?Chunk $chunk = null): string
    {
        if ($chunk?->isLegacyEntry) {
            return $this->makeLegacyScriptTag($url, $chunk);
        }

        return $this->createTag($chunk, tag: 'script', attributes: [
            'type' => 'module',
            'src' => $url,
        ]);
    }

    public function compilePrefetchTag(string $script, ?Chunk $chunk = null): string
    {
        return $this->createTag($chunk, tag: 'script', content: $script);
    }

    public function compileStyleTag(string $url, ?Chunk $chunk = null): string
    {
        return $this->createTag($chunk, tag: 'link', attributes: [
            'rel' => 'stylesheet',
            'href' => $url,
        ]);
    }

    public function compilePreloadTag(string $url, ?Chunk $chunk = null): string
    {
        return $this->createTag($chunk, tag: 'link', attributes: [
            'rel' => 'modulepreload',
            'href' => $url,
        ]);
    }

    private function makeLegacyScriptTag(string $url, ?Chunk $chunk = null): string
    {
        if ($chunk?->src && str_contains($chunk->src, 'vite/legacy-polyfills')) {
            $safariFix = '<script nomodule>!function(){var e=document,t=e.createElement("script");if(!("noModule"in t)&&"onbeforeload"in t){var n=!1;e.addEventListener("beforeload",(function(e){if(e.target===t)n=!0;else if(!e.target.hasAttribute("nomodule")||!n)return;e.preventDefault()}),!0),t.type="module",t.src=".",e.head.appendChild(t),t.remove()}}();</script>';
            $bundleLoader = '<script type="module">!function(){try{new Function("m","return import(m)")}catch(o){console.warn("vite: loading legacy build because dynamic import is unsupported, syntax error above should be ignored");var e=document.getElementById("vite-legacy-polyfill"),n=document.createElement("script");n.src=e.src,n.onload=function(){var entries=Array.prototype.slice.call(document.querySelectorAll("[data-vite-legacy]"),0);entries.forEach(function(entry){System.import(entry.getAttribute("data-src")).catch(console.error)})},document.body.appendChild(n)}}();</script>';
            $legacyBundle = sprintf('<script nomodule id="vite-legacy-polyfill" src="%s"></script>', $url);

            return implode("\r\n", [$safariFix, $bundleLoader, $legacyBundle]);
        }

        return $this->createTag($chunk, tag: 'script', attributes: [
            'nomodule' => true,
            'src' => $url,
        ]);
    }

    private function createTag(?Chunk $chunk, string $tag, array $attributes = [], ?string $content = null): string
    {
        if ($chunk?->integrity) {
            $attributes['integrity'] = $chunk->integrity;
            $attributes['crossorigin'] = 'anonymous';
        }

        if ($this->viteConfig->nonce) {
            $attributes['nonce'] = $this->viteConfig->nonce;
        }

        return HtmlString::createTag($tag, $attributes, $content)->toString();
    }
}
