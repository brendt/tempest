<?php

declare(strict_types=1);

namespace Tempest\Console\Tests\TempestConsoleLanguage\Injections;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Tempest\Console\Highlight\TempestConsoleLanguage\Injections\DynamicInjection;
use Tempest\Console\Highlight\TempestTerminalTheme;
use Tempest\Highlight\Highlighter;

/**
 * @internal
 */
final class DynamicInjectionTest extends TestCase
{
    #[Test]
    #[TestWith(["<style='fg-cyan'>foo</style>", "\e[96mfoo\e[39m"])]
    #[TestWith(['<style="fg-cyan">foo</style>', "\e[96mfoo\e[39m"])]
    #[TestWith(['<style="bg-red">foo</style>', "\e[101mfoo\e[49m"])]
    #[TestWith(['<style="bold">foo</style>', "\e[1mfoo\e[22m"])]
    #[TestWith(['<style="underline">foo</style>', "\e[4mfoo\e[24m"])]
    #[TestWith(['<style="reset">foo</style>', "\e[0mfoo\e[0m"])]
    #[TestWith(['<style="reverse-text">foo</style>', "\e[7mfoo\e[27m"])]
    #[TestWith(['<style="bg-darkcyan fg-cyan underline">Tempest</style>', "\e[46m\e[96m\e[4mTempest\e[49m\e[39m\e[24m"])]
    #[TestWith(['<style="bg-dark-cyan fg-cyan underline">Tempest</style>', "\e[46m\e[96m\e[4mTempest\e[49m\e[39m\e[24m"])]
    #[TestWith(['<style="fg-cyan"><style="bg-dark-red">foo</style></style>', "\e[96m\e[41mfoo\e[49m\e[39m"])]
    #[TestWith(['<style="dim"><style="bg-dark-red fg-white">foo</style></style>', "\e[2m\e[41m\e[97mfoo\e[49m\e[39m\e[22m"])]
    #[TestWith(['<style="fg-cyan">cyan</style>unstyled<style="bg-dark-red">dark red</style>', "\e[96mcyan\e[39munstyled\e[41mdark red\e[49m"])]
    #[TestWith(['<style="dim"><style="fg-gray">dim-gray</style> just-gray</style>', "\e[2m\e[90mdim-gray\e[39m just-gray\e[22m"])]
    public function language(string $content, string $expected): void
    {
        $highlighter = new Highlighter(new TempestTerminalTheme());

        $this->assertSame(
            $expected,
            new DynamicInjection()->parse($content, $highlighter)->content,
        );
    }
}
