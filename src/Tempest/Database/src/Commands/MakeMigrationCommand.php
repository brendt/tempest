<?php

declare(strict_types=1);

namespace Tempest\Database\Commands;

use FilesystemIterator;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tempest\Console\ConsoleArgument;
use Tempest\Console\ConsoleCommand;
use Tempest\Core\Composer;
use Tempest\Core\DoNotDiscover;
use Tempest\Core\PublishesFiles;
use Tempest\Database\DatabaseModel;
use Tempest\Database\Enums\MigrationType;
use Tempest\Database\Stubs\MigrationModelStub;
use Tempest\Database\Stubs\MigrationStub;
use Tempest\Generation\DataObjects\StubFile;
use Tempest\Generation\Exceptions\FileGenerationAbortedException;
use Tempest\Generation\Exceptions\FileGenerationFailedException;
use Tempest\Reflection\ClassReflector;
use Tempest\Validation\Rules\EndsWith;
use Tempest\Validation\Rules\NotEmpty;
use Throwable;
use function Tempest\get;
use function Tempest\Support\arr;
use function Tempest\Support\str;

final class MakeMigrationCommand
{
    use PublishesFiles;

    #[ConsoleCommand(
        name: 'make:migration',
        description: 'Creates a new migration file',
        aliases: ['migration:make', 'migration:create', 'create:migration'],
    )]
    public function __invoke(
        #[ConsoleArgument(
            description: 'The file name of the migration',
        )]
        string $fileName,
        #[ConsoleArgument(
            name: 'type',
            description: 'The type of the migration to create',
        )]
        MigrationType $migrationType = MigrationType::OBJECT,
    ): void {
        try {
            $stubFile = $this->getStubFileFromMigrationType($migrationType);
            $targetPath = match ($migrationType) {
                MigrationType::RAW => $this->generateRawFile($fileName, $stubFile),
                default => $this->generateClassFile($fileName, $stubFile, $migrationType),
            };

            $this->success(sprintf('Migration file successfully created at "%s".', $targetPath));
        } catch (FileGenerationAbortedException|FileGenerationFailedException|InvalidArgumentException $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * Generates a raw migration file.
     * @param string $fileName The name of the file.
     * @param StubFile $stubFile The stub file to use.
     *
     * @return string The path to the generated file.
     */
    private function generateRawFile(
        string $fileName,
        StubFile $stubFile,
    ): string {
        $now = date('Y-m-d');
        $tableName = str($fileName)->snake()->toString();
        $suggestedPath = str($this->getSuggestedPath('Dummy'))
            ->replace(
                [ 'Dummy', '.php' ],
                [ $now . '_' . $tableName, '.sql' ],
            )
            ->toString();

        $targetPath = $this->promptTargetPath($suggestedPath, rules: [
            new NotEmpty(),
            new EndsWith('.sql'),
        ]);
        $shouldOverride = $this->askForOverride($targetPath);

        $this->stubFileGenerator->generateRawFile(
            stubFile: $stubFile,
            targetPath: $targetPath,
            shouldOverride: $shouldOverride,
            replacements: [
                'DummyTableName' => $tableName,
            ],
        );

        return $targetPath;
    }

    /**
     * Generates a class migration file.
     *
     * @param string $fileName The name of the file.
     * @param StubFile $stubFile The stub file to use.
     * @param MigrationType $migrationType The type of the migration.
     *
     * @return string The path to the generated file.
     */
    private function generateClassFile(
        string $fileName,
        StubFile $stubFile,
        MigrationType $migrationType,
    ): string {
        $suggestedPath = $this->getSuggestedPath($fileName);
        $targetPath = $this->promptTargetPath($suggestedPath);
        $shouldOverride = $this->askForOverride($targetPath);
        $tableName = str($fileName)->snake()->toString();
        $replacements = [
            'dummy-date' => date('Y-m-d'),
            'dummy-table-name' => $tableName,
        ];

        if ($migrationType === MigrationType::MODEL) {
            $appModels = $this->getAppDatabaseModels();
            $migrationModel = $this->ask('Model related to the migration', array_keys($appModels));
            $migrationModel = $appModels[$migrationModel] ?? null;
            $migrationModelName = str($migrationModel?->getName() ?? '')->start('\\')->toString();

            $replacements["'DummyModel'"] = sprintf('%s::class', $migrationModelName);
        }

        $this->stubFileGenerator->generateClassFile(
            stubFile: $stubFile,
            targetPath: $targetPath,
            shouldOverride: $shouldOverride,
            replacements: $replacements,
        );

        return $targetPath;
    }

    private function getStubFileFromMigrationType(MigrationType $migrationType): StubFile
    {
        try {
            return match ($migrationType) {
                MigrationType::RAW => StubFile::from(dirname(__DIR__) . '/Stubs/migration.stub.sql'),
                MigrationType::MODEL => StubFile::from(MigrationModelStub::class),
                MigrationType::OBJECT => StubFile::from(MigrationStub::class), // @phpstan-ignore match.alwaysTrue (Because this is a guardrail for the future implementations)
                default => throw new InvalidArgumentException(sprintf('The "%s" migration type has no supported stub file.', $migrationType->value)),
            };
        } catch (InvalidArgumentException $invalidArgumentException) {
            throw new FileGenerationFailedException(sprintf('Cannot retrieve stub file: %s', $invalidArgumentException->getMessage()));
        }
    }

    /**
     * Get database models defined in the application.
     *
     * @return array<string,ClassReflector> The list of models.
     */
    private function getAppDatabaseModels(): array
    {
        $composer = get(Composer::class);
        $directories = new RecursiveDirectoryIterator($composer->mainNamespace->path, flags: FilesystemIterator::UNIX_PATHS | FilesystemIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($directories);
        $databaseModels = [];

        foreach ($files as $file) {
            // We assume that any PHP file that starts with an uppercase letter will be a class
            if ($file->getExtension() !== 'php') {
                continue;
            }
            if (ucfirst($file->getFilename()) !== $file->getFilename()) {
                continue;
            }
            // Try to create a PSR-compliant class name from the path
            $fqcn = str_replace(
                [
                    rtrim($composer->mainNamespace->path, '\\/'),
                    '/',
                    '\\\\',
                    '.php',
                ],
                [
                    $composer->mainNamespace->namespace,
                    '\\',
                    '\\',
                    '',
                ],
                $file->getPathname(),
            );

            // Bail if not a class
            if (! class_exists($fqcn)) {
                continue;
            }

            try {
                $class = new ClassReflector($fqcn);
            } catch (Throwable) {
                continue;
            }

            // Bail if not a database model
            if (! $class->implements(DatabaseModel::class)) {
                continue;
            }

            // Bail if the class should not be discovered
            if ($class->hasAttribute(DoNotDiscover::class)) {
                continue;
            }

            $databaseModels[] = $class;
        }

        return arr($databaseModels)
            ->mapWithKeys(fn (ClassReflector $model) => yield $model->getName() => $model)
            ->toArray();
    }
}
