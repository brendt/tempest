<?php

declare(strict_types=1);

namespace Tempest\View;

use Tempest\Discovery\DiscoversPath;
use Tempest\Discovery\Discovery;
use Tempest\Discovery\DiscoveryLocation;
use Tempest\Discovery\IsDiscovery;
use Tempest\Reflection\ClassReflector;
use Tempest\View\Components\AnonymousViewComponent;

final class ViewComponentDiscovery implements Discovery, DiscoversPath
{
    use IsDiscovery;

    public function __construct(
        private readonly ViewConfig $viewConfig,
    ) {
    }

    public function discover(DiscoveryLocation $location, ClassReflector $class): void
    {
        if (! $class->implements(ViewComponent::class)) {
            return;
        }

        $this->discoveryItems->add($location, [
            forward_static_call($class->getName() . '::getName'),
            $class->getName(),
        ]);
    }

    public function discoverPath(DiscoveryLocation $location, string $path): void
    {
        if (! str_ends_with($path, '.view.php')) {
            return;
        }

        if (! is_file($path)) {
            return;
        }

        $content = ltrim(file_get_contents($path));

        if (! str_contains($content, '<x-component name=')) {
            return;
        }

        preg_match(
            pattern: '/(?<header>(.|\n)*?)<x-component name="(?<name>[\w\-]+)">(?<view>(.|\n)*?)<\/x-component>/',
            subject: $content,
            matches: $matches,
        );

        if (! $matches['name']) {
            return;
        }

        $this->discoveryItems->add($location, [
            $matches['name'],
            new AnonymousViewComponent(
                contents: $matches['header'] . $matches['view'],
                file: $path,
            ),
        ]);
    }

    public function apply(): void
    {
        foreach ($this->discoveryItems as [$name, $viewComponent]) {
            if (is_string($viewComponent)) {
                $viewComponent = new ClassReflector($viewComponent);
            }

            $this->viewConfig->addViewComponent(
                name: $name,
                viewComponent: $viewComponent,
            );
        }
    }
}
