<?php

declare(strict_types=1);

namespace Tempest\Generation;

use Closure;
use Tempest\Generation\DataObjects\StubFile;
use Tempest\Generation\Enums\StubFileType;
use Tempest\Generation\Exceptions\FileGenerationAbortedException;
use Tempest\Generation\Exceptions\FileGenerationFailedException;
use Tempest\Support\NamespaceHelper;
use Tempest\Support\StringHelper;
use Throwable;
use function Tempest\path;
use function Tempest\Support\str;

/**
 * This class can generate a file from a stub file with additional useful methods.
 * It only works with PHP class files.
 */
final class StubFileGenerator
{
    /**
     * @param StubFile $stubFile The stub file to use for the generation. It must be of type CLASS_FILE.
     * @param string $targetPath The path where the generated file will be saved including the filename and extension.
     * @param bool $shouldOverride Whether the generator should override the file if it already exists.
     * @param array<string, string> $replacements An array of key-value pairs to replace in the stub file.
     *     The keys are the placeholders in the stub file (e.g. 'DummyNamespace')
     *     The values are the replacements for the placeholders (e.g. 'App\Models')
     *
     * @param array<Closure(ClassManipulator): ClassManipulator> $manipulations An array of manipulations to apply to the generated class.
     *
     * @throws FileGenerationFailedException
     */
    public function generateClassFile(
        StubFile $stubFile,
        string $targetPath,
        bool $shouldOverride = false,
        array $replacements = [],
        array $manipulations = [],
    ): void {
        try {
            if ($stubFile->type !== StubFileType::CLASS_FILE) {
                throw new FileGenerationFailedException(sprintf('The stub file must be of type CLASS_FILE, <em>%s</em> given.', $stubFile->type->name));
            }

            if (file_exists($targetPath) && ! $shouldOverride) {
                throw new FileGenerationAbortedException(sprintf('The file <em>%s</em> already exists and the operation has been aborted.', $targetPath));
            }

            $this->prepareFilesystem($targetPath);

            // Transform stub to class
            $namespace = NamespaceHelper::toMainNamespace($targetPath);
            $classname = NamespaceHelper::toClassName($targetPath);
            $classManipulator = new ClassManipulator($stubFile->filePath)
                ->setNamespace($namespace)
                ->setClassName($classname);

            foreach ($replacements as $placeholder => $replacement) {
                // @phpstan-ignore function.alreadyNarrowedType
                if (! is_string($replacement)) {
                    continue;
                }

                $classManipulator->manipulate(fn (StringHelper $code) => $code->replace($placeholder, $replacement));
            }

            // Run all manipulations
            $classManipulator = array_reduce(
                array: $manipulations,
                callback: fn (ClassManipulator $manipulator, Closure $manipulation) => $manipulation($manipulator),
                initial: $classManipulator,
            );

            if (file_exists($targetPath) && $shouldOverride) {
                @unlink($targetPath);
            }

            $classManipulator->save($targetPath);
        } catch (Throwable $throwable) {
            throw new FileGenerationFailedException(sprintf('The file could not be written. %s', $throwable->getMessage()));
        }
    }

    /**
     * @param StubFile $stubFile The stub file to use for the generation. It must be of type RAW_FILE.
     * @param string $targetPath The path where the generated file will be saved including the filename and extension.
     * @param bool $shouldOverride Whether the generator should override the file if it already exists.
     * @param array<string, string> $replacements An array of key-value pairs to replace in the stub file.
     *     The keys are the placeholders in the stub file (e.g. 'dummy-content')
     *     The values are the replacements for the placeholders (e.g. 'real content')
     *
     * @param array<Closure(StringHelper): StringHelper> $manipulations An array of manipulations to apply to the generated file raw content.
     *
     * @throws FileGenerationFailedException
     */
    public function generateRawFile(
        StubFile $stubFile,
        string $targetPath,
        bool $shouldOverride = false,
        array $replacements = [],
        array $manipulations = [],
    ): void {
        try {
            if ($stubFile->type !== StubFileType::RAW_FILE) {
                throw new FileGenerationFailedException(sprintf('The stub file must be of type RAW_FILE, "%s" given.', $stubFile->type->name));
            }

            if (file_exists($targetPath) && ! $shouldOverride) {
                throw new FileGenerationAbortedException(sprintf('The file "%s" already exists and the operation has been aborted.', $targetPath));
            }

            $this->prepareFilesystem($targetPath);
            $fileContent = file_get_contents($stubFile->filePath);

            foreach ($replacements as $placeholder => $replacement) {
                // @phpstan-ignore function.alreadyNarrowedType
                if (! is_string($replacement)) {
                    continue;
                }

                $fileContent = str($fileContent)->replace($placeholder, $replacement);
            }

            // Run all manipulations
            $fileContent = array_reduce(
                array: $manipulations,
                initial: $fileContent,
                callback: fn (StringHelper $content, Closure $manipulation) => $manipulation($content),
            );

            if (file_exists($targetPath) && $shouldOverride) {
                @unlink($targetPath);
            }

            file_put_contents($targetPath, $fileContent);
        } catch (Throwable $throwable) {
            throw new FileGenerationFailedException(sprintf('The file could not be written. %s', $throwable->getMessage()));
        }
    }

    /**
     * Prepare the directory structure for the new file.
     * It will delete the target file if it exists and we force the override.
     *
     * @param string $targetPath The path where the generated file will be saved including the filename and extension.
     */
    private function prepareFilesystem(string $targetPath): void
    {
        // Recursively create directories before writing the file
        $directory = dirname($targetPath);
        if (! is_dir($directory)) {
            mkdir($directory, recursive: true);
        }
    }
}
