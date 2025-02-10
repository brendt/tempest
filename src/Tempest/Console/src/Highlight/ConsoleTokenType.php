<?php

declare(strict_types=1);

namespace Tempest\Console\Highlight;

use Tempest\Highlight\Tokens\TokenType;

enum ConsoleTokenType implements TokenType
{
    case EM;
    case STRONG;
    case UNDERLINE;
    case MARK;
    case CODE;

    public function getValue(): string
    {
        return $this->name;
    }

    public function canContain(TokenType $other): bool
    {
        return false;
    }
}
