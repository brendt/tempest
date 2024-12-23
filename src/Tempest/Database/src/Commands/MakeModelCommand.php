<?php

declare(strict_types=1);

namespace Tempest\Database\Commands;

use Tempest\Console\ConsoleArgument;
use Tempest\Console\ConsoleCommand;
use Tempest\Core\PublishesFiles;
use Tempest\Database\Stubs\DatabaseModelStub;
use Tempest\Generation\DataObjects\StubFile;

final class MakeModelCommand
{
    use PublishesFiles;

    #[ConsoleCommand(
        name: 'make:model',
        description: 'Creates a new model class',
        aliases: ['model:make', 'model:create', 'create:model'],
    )]
    public function __invoke(
        #[ConsoleArgument(description: 'The name of the model class to create')]
        string $className,
    ): void {
        $suggestedPath = $this->getSuggestedPath($className);
        $targetPath = $this->promptTargetPath($suggestedPath);
        $shouldOverride = $this->askForOverride($targetPath);

        $this->stubFileGenerator->generateClassFile(
            stubFile: StubFile::from(DatabaseModelStub::class),
            targetPath: $targetPath,
            shouldOverride: $shouldOverride,
        );

        $this->console->success(sprintf('File successfully created at <em>%s</em>.', $targetPath));
    }
}
