<?php

declare(strict_types=1);

namespace Tempest\Filesystem\Tests;

use bovigo\vfs\vfsStream;
use bovigo\vfs\vfsStreamDirectory;
use const PHP_EOL;
use PHPUnit\Framework\TestCase;
use Tempest\Filesystem\ErrorContext;
use Tempest\Filesystem\Exceptions\FileDoesNotExist;
use Tempest\Filesystem\Exceptions\UnableToCopyFile;
use Tempest\Filesystem\Exceptions\UnableToCreateDirectory;
use Tempest\Filesystem\Exceptions\UnableToDeleteFile;
use Tempest\Filesystem\Exceptions\UnableToReadFile;
use Tempest\Filesystem\Exceptions\UnableToWriteFile;
use Tempest\Filesystem\LocalFilesystem;

/**
 * @internal
 */
final class LocalFilesystemTest extends TestCase
{
    private vfsStreamDirectory $root;

    public function test_reading_files(): void
    {
        $filePath = vfsStream::url('root/test.txt');

        file_put_contents($filePath, 'Hello world!');

        $text = (new LocalFilesystem())->read($filePath);

        $this->assertSame('Hello world!', $text);
    }

    public function test_exception_is_thrown_when_reading_a_file_that_doesnt_exist(): void
    {
        $filePath = vfsStream::url('root/does-not-exist.txt');

        $this->expectExceptionObject(
            FileDoesNotExist::atPath($filePath),
        );

        (new LocalFilesystem())->read($filePath);
    }

    public function test_exception_is_thrown_when_there_is_an_error_reading_a_file(): void
    {
        $filePath = vfsStream::url('root/test.txt');

        $this->expectExceptionObject(
            UnableToReadFile::atPath($filePath, new ErrorContext())
        );

        // Make the file unreadable.
        $this->root->getChild('test.txt')->chmod(0000);

        (new LocalFilesystem())->read($filePath);
    }

    public function test_writing_files(): void
    {
        $filePath = __DIR__ . '/test.txt';

        (new LocalFilesystem())->write($filePath, 'Hello world!');

        $this->assertStringEqualsFile($filePath, 'Hello world!');

        unlink($filePath);
    }

    public function test_exception_is_thrown_when_there_is_an_error_writing_a_file(): void
    {
        vfsStream::setup('root', 0000);

        $filePath = vfsStream::url('root/file.txt');

        $this->expectExceptionObject(
            UnableToWriteFile::atPath($filePath, new ErrorContext())
        );

        (new LocalFilesystem())->write($filePath, 'Hello world!');
    }

    public function test_appending_to_a_file(): void
    {
        vfsStream::setup('root', null, [
            'file.txt' => 'Line 1' . PHP_EOL,
        ]);

        $filePath = vfsStream::url('root/file.txt');

        (new LocalFilesystem())->append($filePath, 'Line 2' . PHP_EOL);

        $this->assertStringEqualsFile($filePath, 'Line 1' . PHP_EOL . 'Line 2' . PHP_EOL);
    }

    public function test_exception_is_thrown_when_there_is_an_error_appending_to_a_file(): void
    {
        $this->root->getChild('test.txt')->chmod(0000);

        $filePath = vfsStream::url('root/test.txt');

        $this->expectExceptionObject(
            UnableToWriteFile::atPath($filePath, new ErrorContext())
        );

        (new LocalFilesystem())->append($filePath, 'Line 2' . PHP_EOL);
    }

    public function test_deleting_files(): void
    {
        $filePath = vfsStream::url('root/test.txt');

        (new LocalFilesystem())->delete($filePath);

        $this->assertFileDoesNotExist($filePath);
    }

    public function test_exception_is_thrown_when_there_is_an_error_deleting_a_file(): void
    {
        vfsStream::setup('root', 0000, [
            'file.txt' => 'Hello world!',
        ]);

        $filePath = vfsStream::url('root/file.txt');

        $this->expectExceptionObject(
            UnableToDeleteFile::atPath($filePath)
        );

        (new LocalFilesystem())->delete($filePath);
    }

    public function test_is_file(): void
    {
        $filesystem = new LocalFilesystem();
        $directoryPath = vfsStream::url('root/test-directory');
        $filePath = vfsStream::url('root/test.txt');

        $this->assertFalse($filesystem->isFile($directoryPath));
        $this->assertTrue($filesystem->isFile($filePath));
    }

    public function test_checking_file_existence(): void
    {
        $filePath = vfsStream::url('root/some-file.txt');

        $this->assertFalse(
            (new LocalFilesystem())->exists($filePath)
        );

        file_put_contents($filePath, 'Hello world!');

        $this->assertTrue(
            (new LocalFilesystem())->exists($filePath)
        );
    }

    public function test_copying_files(): void
    {
        $filePath1 = vfsStream::url('root/test.txt');
        $filePath2 = vfsStream::url('root/test2.txt');

        (new LocalFilesystem())->copy($filePath1, $filePath2);

        $this->assertFileEquals($filePath2, $filePath1);
    }

    public function test_exception_is_thrown_if_source_file_doesnt_exist_when_copying(): void
    {
        $filePath1 = vfsStream::url('some-file.txt');
        $filePath2 = vfsStream::url('some-other-file.txt');

        $this->expectExceptionObject(
            FileDoesNotExist::atPath($filePath1)
        );

        (new LocalFilesystem())->copy($filePath1, $filePath2);
    }

    public function test_exception_is_thrown_if_there_is_an_error_copying(): void
    {
        $filePath1 = vfsStream::url('root/test.txt');
        $filePath2 = vfsStream::url('root/nested-dir/test2.txt');

        $this->expectExceptionObject(
            UnableToCopyFile::fromSourceToDestination($filePath1, $filePath2, new ErrorContext())
        );

        (new LocalFilesystem())->copy($filePath1, $filePath2);
    }

    public function test_moving_files(): void
    {
        $filePath1 = vfsStream::url('root/test.txt');
        $filePath2 = vfsStream::url('root/test2.txt');

        (new LocalFilesystem())->move($filePath1, $filePath2);

        $this->assertFileDoesNotExist($filePath1);
        $this->assertStringEqualsFile($filePath2, 'Hello world!');
    }

    public function test_making_a_directory(): void
    {
        $directoryPath = vfsStream::url('root/some-dir');

        (new LocalFilesystem())->createDirectory($directoryPath);

        $this->assertDirectoryExists($directoryPath);
    }

    public function test_making_a_directory_recursively(): void
    {
        $directoryPath = vfsStream::url('root/some-dir/nested-some-dir');

        (new LocalFilesystem())->createDirectory($directoryPath);

        $this->assertDirectoryExists($directoryPath);
    }

    public function test_making_a_directory_recursively_without_recursive_enabled_fails(): void
    {
        $directoryPath = vfsStream::url('root/some-dir/nested-some-dir');

        // TODO: Update
        $this->expectException(UnableToCreateDirectory::class);

        (new LocalFilesystem())->createDirectory(
            path: $directoryPath,
            recursive: false
        );
    }

    public function test_is_directory(): void
    {
        $filesystem = new LocalFilesystem();
        $directory = vfsStream::url('root/test-directory/nested-test-directory');

        $this->assertFalse($filesystem->isDirectory($directory));

        $filesystem->createDirectory($directory);

        $this->assertTrue($filesystem->isDirectory($directory));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = vfsStream::setup('root', null, [
            'test-directory' => [],
            'test.txt' => 'Hello world!',
        ]);
    }
}
