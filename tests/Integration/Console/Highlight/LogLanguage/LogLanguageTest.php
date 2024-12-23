<?php

declare(strict_types=1);

namespace Tests\Tempest\Integration\Console\Highlight\LogLanguage;

use PHPUnit\Framework\TestCase;
use Tempest\Console\Highlight\LogLanguage\LogLanguage;
use Tempest\Console\Highlight\TempestTerminalTheme;
use Tempest\Highlight\Highlighter;

/**
 * @internal
 */
final class LogLanguageTest extends TestCase
{
    public function test_language(): void
    {
        $highlighter = new Highlighter(new TempestTerminalTheme());

        $parsed = $highlighter->parse('[2024-05-25T18:42:01.154192+00:00] tempest.DEBUG: logger [] []', new LogLanguage());

        $this->assertSame("\e[94m[2024-05-25T18:42:01.154192+00:00]\e[39m \e[4mtempest.DEBUG\e[24m: logger [] []", $parsed);
    }
}
