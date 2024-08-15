<?php

declare(strict_types=1);

namespace Tempest\Support\VarExport;

use Symfony\Component\VarExporter\VarExporter;

/**
 * A wrapper around a PHP file to export variables to. This enables us to take advantage of OPcache file cache.
 *
 * @template T type of exported data
 */
final readonly class VarExportPhpFile
{
    public function __construct(
        public string $filename,
    ) {
        if ($this->filename === '') {
            throw new EmptyFileNameException("Filename MUST NOT be empty!");
        }
    }

    public function exists(): bool
    {
        return file_exists($this->filename);
    }

    /**
     * @return T
     */
    public function import(): mixed
    {
        if (! $this->exists()) {
            throw new FileDoesNotExistException("The required VarExport File does not exist!");
        }

        return require $this->filename;
    }

    /**
     * @param T $data
     */
    public function export(mixed $data): void
    {
        $serializedData = VarExporter::export($data);

        $phpFileContent = <<<PHP
        <?php // cache file generated by tempest. Do not edit manually.
        return {$serializedData};
        PHP;

        file_put_contents($this->filename, $phpFileContent, LOCK_EX);
    }

    public function destroy(): void
    {
        @unlink($this->filename);
    }
}
