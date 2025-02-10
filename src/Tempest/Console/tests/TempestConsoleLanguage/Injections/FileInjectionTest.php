<?php

declare(strict_types=1);

namespace Tempest\Console\Tests\TempestConsoleLanguage\Injections;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Tempest\Console\Highlight\TempestConsoleLanguage\Injections\FileInjection;
use Tempest\Console\Highlight\TempestTerminalTheme;
use Tempest\Highlight\Highlighter;

/**
 * @internal
 */
final class FileInjectionTest extends TestCase
{
    #[Test]
    #[TestWith(["<file='file.txt'/>", 'file.txt'])]
    public function language(string $content, string $expected): void
    {
        $highlighter = new Highlighter(new TempestTerminalTheme());

        $this->assertStringContainsString(
            $expected,
            (new FileInjection())->parse($content, $highlighter)->content,
        );
    }
}
