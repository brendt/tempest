<?php

declare(strict_types=1);

namespace Tempest\Router\Static;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tempest\Console\ConsoleCommand;
use Tempest\Console\HasConsole;
use Tempest\Console\Middleware\CautionMiddleware;
use Tempest\Console\Middleware\ForceMiddleware;
use Tempest\Core\Kernel;

final readonly class StaticCleanCommand
{
    use HasConsole;

    public function __construct(
        private Kernel $kernel,
    ) {
    }

    #[ConsoleCommand(
        name: 'static:clean',
        description: 'Removes statically generated pages',
        middleware: [ForceMiddleware::class, CautionMiddleware::class],
    )]
    public function __invoke(): void
    {
        $directoryIterator = new RecursiveDirectoryIterator($this->kernel->root.'/public');
        $directoryIterator->setFlags(FilesystemIterator::SKIP_DOTS);

        $this->removeFiles($directoryIterator);
        $this->removeEmptyDirectories($directoryIterator);

        $this->success('Done.');
    }

    private function removeFiles(RecursiveDirectoryIterator $directoryIterator): void
    {
        /** @var SplFileInfo[] $files */
        $files = [];

        /** @var SplFileInfo $file */
        foreach (new RecursiveIteratorIterator($directoryIterator) as $file) {
            if ($file->getExtension() === 'html') {
                $files[] = $file;
            }
        }

        foreach ($files as $file) {
            unlink($file->getPathname());

            $pathName = str_replace('\\', '/', $file->getPathname());

            $this->writeln("- <u>{$pathName}</u> removed");
        }
    }

    private function removeEmptyDirectories(RecursiveDirectoryIterator $directoryIterator): void
    {
        /** @var SplFileInfo $file */
        foreach (new RecursiveIteratorIterator($directoryIterator, RecursiveIteratorIterator::CHILD_FIRST) as $file) {
            if (! $file->isDir()) {
                continue;
            }

            if (count(glob($file->getPathname().'/*')) > 0) {
                continue;
            }

            rmdir($file->getPathname());

            $pathName = str_replace('\\', '/', $file->getPathname());

            $this->writeln("- <u>{$pathName}</u> directory removed");
        }
    }
}
