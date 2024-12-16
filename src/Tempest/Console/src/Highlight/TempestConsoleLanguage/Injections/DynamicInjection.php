<?php

declare(strict_types=1);

namespace Tempest\Console\Highlight\TempestConsoleLanguage\Injections;

use Tempest\Console\Highlight\DynamicTokenType;
use Tempest\Highlight\Highlighter;
use Tempest\Highlight\Injection;
use Tempest\Highlight\ParsedInjection;
use function Tempest\Support\str;

final readonly class DynamicInjection implements Injection
{
    public function parse(string $content, Highlighter $highlighter): ParsedInjection
    {
        $pattern = '/(?<match>\<style=(?<quote>[\"\'])(?<styles>(?:[a-z-]+\s*)+)\k<quote>\>(?:(?!\<style).|\n)*?\<\/style\>)/';

        do {
            $content = preg_replace_callback(
                subject: $content,
                pattern: $pattern,
                callback: function ($matches) use ($highlighter) {
                    $theme = $highlighter->getTheme();
                    $quote = $matches['quote'];
                    $match = $matches['match'];
                    $styles = $matches['styles'];
                    $before = '';
                    $after = '';

                    foreach (explode(' ', $styles) as $style) {
                        $token = new DynamicTokenType($style);
                        $before .= $theme->before($token);
                        $after .= $theme->after($token);
                    }

                    return str($match)
                        ->replaceFirst("<style={$quote}{$styles}{$quote}>", $before)
                        ->replaceLast('</style>', $after)
                        ->toString();
                },
            );
        } while (preg_match($pattern, $content));

        return new ParsedInjection($content);
    }
}
