<?php

declare(strict_types=1);

namespace Tempest\Console\Highlight\TempestConsoleLanguage\Injections;

use Tempest\Highlight\Highlighter;
use Tempest\Highlight\Injection;
use Tempest\Highlight\ParsedInjection;
use function Tempest\Support\str;

final readonly class LinkInjection implements Injection
{
    public function parse(string $content, Highlighter $highlighter): ParsedInjection
    {
        return new ParsedInjection(preg_replace_callback(
            subject: $content,
            pattern: '/(?<match>\<href=(?<quote>[\"\'])(?<href>.+)\k<quote>\>(?:(?!\<href).)*?\<\/href\>)/',
            callback: function (array $matches) {
                $quote = $matches['quote'];
                $match = $matches['match'];
                $href = $matches['href'];

                return str($match)
                    ->replaceFirst("<href={$quote}{$href}{$quote}>", "\x1b]8;;{$href}\x1b\\")
                    ->replaceLast('</href>', "\x1b]8;;\x1b\\")
                    ->toString();
            },
        ));
    }
}
