<?php

declare(strict_types=1);

namespace Tempest\View\Commands;

use function Tempest\Support\str;
use Tempest\View\Stubs\ViewStub;
use Tempest\View\Enums\ViewType;
use Tempest\Generation\Exceptions\FileGenerationFailedException;
use Tempest\Generation\Exceptions\FileGenerationAbortedException;
use Tempest\Generation\Enums\StubFileType;
use Tempest\Generation\DataObjects\StubFile;
use Tempest\Core\PublishesFiles;
use Tempest\Console\ConsoleCommand;
use Tempest\Console\ConsoleArgument;
use InvalidArgumentException;

final class MakeViewCommand
{
    use PublishesFiles;

    #[ConsoleCommand(
        name: 'make:view',
        description: 'Creates a view file',
        aliases: ['view:make', 'view:create', 'create:view'],
    )]
    public function __invoke(
        #[ConsoleArgument(
            help: 'The file name of the view',
        )]
        string $fileName,
        #[ConsoleArgument(
            name: 'type',
            help: 'The type of the view to create',
        )]
        ViewType $viewType = ViewType::RAW,
    ): void {
        try {
            $suggestedPath = str($this->getSuggestedPath('Dummy'));
            $suggestedPath = ( $viewType === ViewType::RAW )
                ? $suggestedPath->replace('Dummy', $fileName . '.view')
                : $suggestedPath->replace('Dummy', $fileName);

            $suggestedPath = $suggestedPath->toString();
            $targetPath = $this->promptTargetPath($suggestedPath);
            $shouldOverride = $this->askForOverride($targetPath);

            $stubFile = $this->getStubFileFromViewType($viewType);

            if ( $stubFile->type === StubFileType::RAW_FILE ) {
                $this->stubFileGenerator->generateRawFile(
                    stubFile: $stubFile,
                    targetPath: $targetPath,
                    shouldOverride: $shouldOverride,
                );
            } else {
                $this->stubFileGenerator->generateClassFile(
                    stubFile: $stubFile,
                    targetPath: $targetPath,
                    shouldOverride: $shouldOverride,
                    replacements: [
                        'dummy.view.php' => str($fileName)->kebab()->toString() . '.view.php',
                    ]
                );
            }

            $this->success(sprintf('View successfully created at "%s".', $targetPath));
        } catch (FileGenerationAbortedException|FileGenerationFailedException|InvalidArgumentException $e) {
            $this->error($e->getMessage());
        }
    }
    
    private function getStubFileFromViewType(ViewType $viewType): StubFile
    {
        try {
            return match ($viewType) {
                ViewType::RAW => StubFile::from(dirname(__DIR__) . '/Stubs/view.stub.php'),
                ViewType::OBJECT => StubFile::from(ViewStub::class), // @phpstan-ignore match.alwaysTrue (Because this is a guardrail for the future implementations)
                default => throw new InvalidArgumentException(sprintf('The "%s" view type has no supported stub file.', $viewType->value)),
            };
        } catch (InvalidArgumentException $invalidArgumentException) {
            throw new FileGenerationFailedException(sprintf('Cannot retrieve stub file: %s', $invalidArgumentException->getMessage()));
        }
    }
}