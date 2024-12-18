<?php

declare(strict_types=1);

namespace Tempest\Database\Commands;

use Tempest\Generation\Exceptions\FileGenerationFailedException;
use Tempest\Generation\Exceptions\FileGenerationAbortedException;
use Tempest\Generation\Enums\StubFileType;
use Tempest\Generation\DataObjects\StubFile;
use Tempest\Database\Stubs\MigrationStub;
use Tempest\Database\Enums\MigrationType;
use Tempest\Core\PublishesFiles;
use Tempest\Console\ConsoleCommand;
use Tempest\Console\ConsoleArgument;
use InvalidArgumentException;
use Tempest\Database\Stubs\MigrationModelStub;
use Tempest\Validation\Rules\EndsWith;
use Tempest\Validation\Rules\NotEmpty;

use function Tempest\get;

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
            help: 'The file name of the migration',
        )]
        string $fileName,
        #[ConsoleArgument(
            name: 'type',
            help: 'The type of the migration to create',
        )]
        MigrationType $migrationType = MigrationType::OBJECT,
    ): void {
        try {
            $stubFile = $this->getStubFileFromMigrationType($migrationType);
            $targetPath = match ($migrationType) {
                MigrationType::RAW => $this->generateRawFile( $fileName, $stubFile ),
                default => $this->generateClassFile( $fileName, $stubFile, $migrationType ),
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
    protected function generateRawFile(
        string $fileName,
        StubFile $stubFile,
    ): string {
        $now = date('Y-m-d');
        $tableName = str($fileName)->snake()->toString();
        $suggestedPath = str($this->getSuggestedPath('Dummy'))
            ->replace(
                [ 'Dummy', '.php' ],
                [ $now . '_' . $tableName, '.sql' ]
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
    protected function generateClassFile(
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
        if ( $migrationType === MigrationType::MODEL ) {
            $migrationModel = $this->search('Model related to the migration', function( string $search ) {
                // @TODO : Implement the search logic to find all models in app
                return [
                    'BookModel',
                    'AuthorModel',
                ];
            });

            $replacements['DummyModel'] = $migrationModel;
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
}
