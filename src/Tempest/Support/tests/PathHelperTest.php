<?php

declare(strict_types=1);

namespace Tempest\Support\Tests;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tempest\Support\NamespaceHelper;
use function Tempest\Support\path;

/**
 * @internal
 */
final class PathHelperTest extends TestCase
{
    #[DataProvider('paths')]
    public function test_make(array $paths, string $expected): void
    {
        // Act
        $output = path(...$paths)->toString();

        // Assert
        $this->assertSame($expected, $output);
    }

    public static function paths(): Generator
    {
        yield 'single path' => [
            'paths' => ['/foo/'],
            'expected' => '/foo/',
        ];

        yield 'single path with forward slash' => [
            'paths' => ['/foo/bar/'],
            'expected' => '/foo/bar/',
        ];

        yield 'single path with backward slash' => [
            'paths' => ['\\foo\\bar\\'],
            'expected' => '/foo/bar/',
        ];

        yield 'multiple paths' => [
            'paths' => ['foo', 'bar'],
            'expected' => 'foo/bar',
        ];

        yield 'multiple paths with forward slash' => [
            'paths' => ['/foo/bar/', '/baz/qux/'],
            'expected' => '/foo/bar/baz/qux/',
        ];

        yield 'multiple paths with backward slash' => [
            'paths' => ['\\foo\\bar\\', '\\baz\\qux\\'],
            'expected' => '/foo/bar/baz/qux/',
        ];

        yield 'single forward slash' => [
            'paths' => ['/'],
            'expected' => '/',
        ];

        yield 'single backward slash' => [
            'paths' => ['\\'],
            'expected' => '/',
        ];

        yield 'no slash' => [
            'paths' => ['foo'],
            'expected' => 'foo',
        ];

        yield 'starts with forward slash' => [
            'paths' => ['/foo'],
            'expected' => '/foo',
        ];

        yield 'starts with backward slash' => [
            'paths' => ['\\foo'],
            'expected' => '/foo',
        ];

        yield 'ends with forward slash' => [
            'paths' => ['foo/'],
            'expected' => 'foo/',
        ];

        yield 'ends with backward slash' => [
            'paths' => ['foo\\'],
            'expected' => 'foo/',
        ];

        yield 'first path is forward slash' => [
            'paths' => ['/', '/foo'],
            'expected' => '/foo',
        ];

        yield 'first path is backward slash' => [
            'paths' => ['\\', '\\foo'],
            'expected' => '/foo',
        ];

        yield 'last path is forward slash' => [
            'paths' => ['foo/', '/'],
            'expected' => 'foo/',
        ];

        yield 'last path is backward slash' => [
            'paths' => ['foo\\', '\\'],
            'expected' => 'foo/',
        ];
    }

    #[DataProvider('toClassNameProvider')]
    #[Test]
    public function toClassName(string $path, string $expected): void
    {
        $this->assertSame(
            expected: $expected,
            actual: NamespaceHelper::toClassName($path),
        );
    }

    public static function toClassNameProvider(): array
    {
        return [
            'single path' => ['/Foo/Bar', 'Bar'],
            'single path end with forward slash' => ['Foo/Bar/', 'Bar'],
            'single path end with backward slash' => ['Foo/Bar\\', 'Bar'],
            'path with extension' => ['Foo/Bar.php', 'Bar'],
        ];
    }
}
