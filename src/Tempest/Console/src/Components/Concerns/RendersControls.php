<?php

declare(strict_types=1);

namespace Tempest\Console\Components\Concerns;

use Tempest\Console\Components\ComponentState;
use Tempest\Console\InteractiveConsoleComponent;
use Tempest\Console\Terminal\Terminal;
use Tempest\Support\StringHelper;
use function Tempest\Support\arr;

/**
 * @mixin InteractiveConsoleComponent
 * @phpstan-require-implements InteractiveConsoleComponent
 */
trait RendersControls
{
    public function renderFooter(Terminal $terminal): ?string
    {
        if (in_array($this->getState(), [ComponentState::CANCELLED, ComponentState::SUBMITTED, ComponentState::BLOCKED])) {
            return null;
        }

        return $this->renderControls($this->getControls(), maxWidth: $terminal->width);
    }

    private function getControls(): array
    {
        return [
            'enter' => 'confirm',
            'ctrl+c' => 'cancel',
        ];
    }

    private function renderControls(array $controls, int $maxWidth): string
    {
        if ($controls === []) {
            return '';
        }

        $separator = '  <style="dim fg-gray">·</style>  ';
        $marginLeft = '  ';
        $render = arr($controls)
            ->map(fn (string $label, string $shortcut) => "<style=\"dim\"><style=\"fg-gray\">{$shortcut}</style> {$label}</style>")
            ->implode($separator)
            ->prepend($marginLeft);

        if ($render->stripTags()->length() >= $maxWidth) {
            $prefix = $marginLeft . '<style="fg-gray">·</style>';
            $render = new StringHelper($prefix)
                ->append($render)
                ->explode($separator)
                ->implode("\n" . $prefix . $marginLeft);
        }

        return $render->toString();
    }
}
