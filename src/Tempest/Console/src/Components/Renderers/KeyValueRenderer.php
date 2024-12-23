<?php

declare(strict_types=1);

namespace Tempest\Console\Components\Renderers;

use Stringable;
use Tempest\Support\StringHelper;
use function Tempest\root_path;
use function Tempest\Support\str;

final readonly class KeyValueRenderer
{
    private const int MAX_WIDTH = 125;

    private const int MIN_WIDTH = 3;

    public function render(Stringable|string $key, null|Stringable|string $value = null, int $maximumWidth = self::MAX_WIDTH): string
    {
        $key = $this->cleanText($key)->append(' ');
        $value = $this->cleanText($value)->unless(
            condition: fn ($s) => $s->stripTags()->length() === 0,
            callback: fn ($s) => $s->prepend(' '),
        );

        $dotsWidth = $maximumWidth - $key->stripTags()->length() - $value->stripTags()->length();

        return str()
            ->append($key)
            ->append('<style="fg-gray dim">', str_repeat('.', max(self::MIN_WIDTH, min($dotsWidth, self::MAX_WIDTH))), '</style>')
            ->append($value)
            ->toString();
    }

    private function cleanText(null|Stringable|string $text): StringHelper
    {
        $text = str($text)->trim();

        if ($text->length() === 0) {
            return str();
        }

        return $text
            ->replaceRegex('/\[([^]]+)]/', '<em>[$1]</em>')
            ->when(fn ($s) => $s->endsWith(['.', '?', '!', ':']), fn ($s) => $s->replaceAt(-1, 1, ''))
            ->replace(root_path(), '')
            ->trim();
    }
}
