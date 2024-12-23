<?php

declare(strict_types=1);

namespace Tempest\Console\Highlight\LogLanguage\Patterns;

use Tempest\Console\Highlight\DynamicTokenType;
use Tempest\Highlight\IsPattern;
use Tempest\Highlight\Pattern;
use Tempest\Highlight\Tokens\TokenType;

final readonly class LogNamePattern implements Pattern
{
    use IsPattern;

    public function getPattern(): string
    {
        return '/^(\[.*?\]) (?<match>[\w\.]+)/';
    }

    public function getTokenType(): TokenType
    {
        return new DynamicTokenType('underline');
    }
}
