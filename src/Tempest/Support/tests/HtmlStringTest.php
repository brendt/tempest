<?php

declare(strict_types=1);

namespace Tempest\Support\Tests;

use PHPUnit\Framework\TestCase;
use Stringable;
use Tempest\Support\HtmlString;
use Tempest\Support\StringHelper;

/**
 * @internal
 */
final class HtmlStringTest extends TestCase
{
    public function test_conversions(): void
    {
        $this->assertInstanceOf(HtmlString::class, HtmlString::createTag('div'));
        $this->assertInstanceOf(Stringable::class, HtmlString::createTag('div'));
        $this->assertInstanceOf(StringHelper::class, HtmlString::createTag('div')->toStringHelper());
        $this->assertSame('<div></div>', HtmlString::createTag('div')->toStringHelper()->toString());
    }

    public function test_create_tag(): void
    {
        $this->assertSame(
            expected: '<div></div>',
            actual: (string) HtmlString::createTag('div'),
        );

        $this->assertSame(
            expected: '<button type="submit">OK</button>',
            actual: (string) HtmlString::createTag('button', ['type' => 'submit'], 'OK'),
        );

        $this->assertSame(
            expected: '<a href="https://example.com">Link</a>',
            actual: (string) HtmlString::createTag('a', ['href' => 'https://example.com'], 'Link'),
        );

        $this->assertSame(
            expected: '<script src="https://example.com/script.js"></script>',
            actual: (string) HtmlString::createTag('script', ['src' => 'https://example.com/script.js']),
        );

        $this->assertSame(
            expected: '<link href="https://example.com/style.css" rel="stylesheet" />',
            actual: (string) HtmlString::createTag('link', ['href' => 'https://example.com/style.css', 'rel' => 'stylesheet']),
        );

        $this->assertSame(
            expected: '<img src="https://example.com/image.jpg" alt="An image" />',
            actual: (string) HtmlString::createTag('img', ['src' => 'https://example.com/image.jpg', 'alt' => 'An image']),
        );

        $this->assertSame(
            expected: '<input type="checkbox" checked />',
            actual: (string) HtmlString::createTag('input', ['type' => 'checkbox', 'checked' => true]),
        );

        $this->assertSame(
            expected: '<input type="checkbox" />',
            actual: (string) HtmlString::createTag('input', ['type' => 'checkbox', 'checked' => false]),
        );
    }
}
