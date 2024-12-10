<?php

declare(strict_types=1);

namespace Tests\Tempest\Integration\Vite;

use Tempest\Vite\BuildConfig;
use Tempest\Vite\Exceptions\EntrypointNotFoundException;
use Tempest\Vite\Vite;
use Tempest\Vite\ViteConfig;
use Tests\Tempest\Integration\FrameworkIntegrationTestCase;

/**
 * @internal
 */
final class ViteTest extends FrameworkIntegrationTestCase
{
    use HasFixtures;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vite->setRootDirectory(__DIR__ . '/Fixtures/tmp');
    }

    public function test_set_nonce(): void
    {
        $vite = $this->container->get(Vite::class);
        $vite->setNonce('expected-nonce');

        $config = $this->container->get(ViteConfig::class);
        $this->assertSame('expected-nonce', $config->nonce);
    }

    public function test_get_tags_in_development_with_custom_entrypoints(): void
    {
        $this->vite->callWithFiles(
            callback: function (): void {
                $vite = $this->container->get(Vite::class);
                $tags = $vite->getTags(['src/main.ts']);

                $this->assertSame(
                    expected: '<script type="module" src="http://localhost:5173/@vite/client"></script><script type="module" src="http://localhost:5173/src/main.ts"></script>',
                    actual: $tags,
                );
            },
            files: [
                'public/vite-tempest' => ['url' => 'http://localhost:5173'],
            ],
        );
    }

    public function test_get_tags_in_development_with_configured_entrypoints(): void
    {
        $this->vite->callWithFiles(
            callback: function (): void {
                $this->container->config(new ViteConfig(
                    build: new BuildConfig(
                        entrypoints: ['src/foo.ts', 'src/bar.css'],
                    ),
                ));

                $vite = $this->container->get(Vite::class);
                $tags = $vite->getTags();

                $this->assertSame(
                    expected: '<script type="module" src="http://localhost:5173/@vite/client"></script><script type="module" src="http://localhost:5173/src/foo.ts"></script><link rel="stylesheet" href="http://localhost:5173/src/bar.css" />',
                    actual: $tags,
                );
            },
            files: [
                'public/vite-tempest' => ['url' => 'http://localhost:5173'],
            ],
        );
    }

    public function test_get_tags_with_manifest_with_specified_entrypoints(): void
    {
        $this->vite->callWithFiles(
            callback: function (): void {
                $vite = $this->container->get(Vite::class);
                $tags = $vite->getTags(['src/main.ts']);

                $this->assertSame(
                    expected: '<script type="module" src="build/assets/main-YJD4Cw3J.js"></script>',
                    actual: $tags,
                );
            },
            files: [
                'public/build/manifest.json' => $this->fixture('simple-manifest.json'),
            ],
        );
    }

    public function test_throws_when_getting_tags_with_manifest_with_unknown_entrypoint(): void
    {
        $this->expectException(EntrypointNotFoundException::class);

        $this->vite->callWithFiles(
            callback: function (): void {
                $vite = $this->container->get(Vite::class);
                $vite->getTags(['src/file-that-does-not-exist.ts']);
            },
            files: [
                'public/build/manifest.json' => $this->fixture('simple-manifest.json'),
            ],
        );
    }

    public function test_get_tags_with_manifest_with_css(): void
    {
        $this->vite->callWithFiles(
            callback: function (): void {
                $vite = $this->container->get(Vite::class);
                $tags = $vite->getTags(['src/main.ts']);

                $this->assertSame(
                    expected: '<link rel="stylesheet" href="build/assets/main-DObprJ9K.css" /><script type="module" src="build/assets/main-CK61jJwL.js"></script>',
                    actual: $tags,
                );
            },
            files: [
                'public/build/manifest.json' => $this->fixture('simple-manifest-with-css.json'),
            ],
        );
    }

    public function test_get_tags_with_manifest_and_preloading(): void
    {
        $this->vite->callWithFiles(
            callback: function (): void {
                $vite = $this->container->get(Vite::class);
                $tags = $vite->getTags(['resources/js/app.js']);

                $this->assertSame(
                    expected: '<link rel="modulepreload" href="build/assets/index-BSdK3M0e.js" /><link rel="stylesheet" href="build/assets/index-B3s1tYeC.css" /><script type="module" src="build/assets/app-lliD09ip.js"></script>',
                    actual: $tags,
                );
            },
            files: [
                'public/build/manifest.json' => $this->fixture('prefetching-manifest.json'),
            ],
        );
    }

    public function test_get_tags_with_entrypoints_and_global_entrypoints(): void
    {
        $this->vite->callWithFiles(
            callback: function (): void {
                $this->container->config(new ViteConfig(
                    testing: true,
                    build: new BuildConfig(
                        entrypoints: ['src/foo.ts'],
                    ),
                ));

                $vite = $this->container->get(Vite::class);
                $tags = $vite->getTags(['src/bar.ts']);

                $this->assertSame(
                    expected: '<script type="module" src="build/assets/bar-WlXl03ld.js"></script>',
                    actual: $tags,
                );
            },
            files: [
                'public/build/manifest.json' => $this->fixture('two-unrelated-entrypoints.json'),
            ],
        );
    }
}
