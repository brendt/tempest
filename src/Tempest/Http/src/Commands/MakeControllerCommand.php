<?php

declare(strict_types=1);

namespace Tempest\Http\Commands;

use Tempest\Console\ConsoleArgument;
use Tempest\Console\ConsoleCommand;
use Tempest\Generation\DataObjects\StubFile;
use Tempest\Generation\Exceptions\FileGenerationAbortedException;
use Tempest\Generation\Exceptions\FileGenerationFailedException;
use Tempest\Generation\HasGeneratorConsoleInteractions;
use Tempest\Http\Stubs\ControllerStub;

final class MakeControllerCommand
{
    use HasGeneratorConsoleInteractions;

    #[ConsoleCommand(
        name: 'make:controller',
        description: 'Creates a new controller class with a route',
        aliases: ['controller:make', 'controller:create', 'create:controller'],
    )]
    public function __invoke(
        #[ConsoleArgument(
            help: 'The name of the controller class to create',
        )]
        string $className,
        #[ConsoleArgument(
            help: 'The path of the route',
        )]
        ?string $controllerPath = null,
        #[ConsoleArgument(
            help: 'The name of the view returned from the controller',
        )]
        ?string $controllerView = null,
    ): void {
        $suggestedPath = $this->getSuggestedPath($className);
        $targetPath = $this->promptTargetPath($suggestedPath);
        $shouldOverride = $this->askForOverride($targetPath);

        try {
            $this->stubFileGenerator->generateClassFile(
                stubFile: StubFile::fromClassString(ControllerStub::class),
                targetPath: $targetPath,
                shouldOverride: $shouldOverride,
                replacements: [
                    'dummy-path' => $controllerPath,
                    'dummy-view' => $controllerView,
                ],
            );

            $this->console->success(sprintf('File successfully created at "%s".', $targetPath));
        } catch (FileGenerationAbortedException|FileGenerationFailedException $e) {
            $this->console->error($e->getMessage());
        }
    }
}
