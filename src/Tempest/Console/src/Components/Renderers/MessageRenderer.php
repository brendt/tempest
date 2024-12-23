<?php

declare(strict_types=1);

namespace Tempest\Console\Components\Renderers;

use Tempest\Support\ArrayHelper;
use function Tempest\Support\str;

final readonly class MessageRenderer
{
    public function __construct(
        private string $label,
        private string $color,
    ) {
    }

    public function render(string $contents, ?string $title = null): string
    {
        $title = str($title ?? $this->label)->toString();
        $lines = ArrayHelper::explode($contents, "\n")
            ->map(fn ($s, $i) => str_repeat(' ', ($i === 0 ? 1 : strlen($title) + 4)) . $s)
            ->implode("\n");

        return str()
            ->append("\n")
            ->append("<style='fg-{$this->color} bold'>{$title}</style> <style='dim fg-{$this->color}'>//</style>")
            ->append("<style='fg-{$this->color}'>{$lines}</style>")
            ->toString();
    }
}
