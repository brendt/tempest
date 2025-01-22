<?php

declare(strict_types=1);

namespace Tempest\Console\Tests\TempestConsoleLanguage\Injections;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Tempest\Console\Highlight\TempestConsoleLanguage\Injections\LinkInjection;
use Tempest\Console\Highlight\TempestTerminalTheme;
use Tempest\Highlight\Highlighter;

/**
 * @internal
 */
final class LinkInjectionTest extends TestCase
{
    #[Test]
    #[TestWith(['<href="https://tempestphp.com">Tempest</href>', "\e]8;;https://tempestphp.com\e\Tempest\e]8;;\e\\"])]
    #[TestWith(['<href="http://example.com/path?param=value!@#$%^&*()_+-={}:<>?,./">My link</href>', "\e]8;;http://example.com/path?param=value!@#$%^&*()_+-={}:<>?,./\e\My link\e]8;;\e\\"])]
    #[TestWith(['<href="tel:+1234567890">My link</href>', "\e]8;;tel:+1234567890\e\My link\e]8;;\e\\"])]
    #[TestWith(['<href="mailto:user@example.com">My link</href>', "\e]8;;mailto:user@example.com\e\My link\e]8;;\e\\"])]
    public function language(string $content, string $expected): void
    {
        $highlighter = new Highlighter(new TempestTerminalTheme());

        $this->assertSame(
            $expected,
            new LinkInjection()->parse($content, $highlighter)->content,
        );
    }
}
