<?php

declare(strict_types=1);

namespace Tempest\Container\Commands;

use Tempest\Console\ConsoleArgument;
use Tempest\Console\ConsoleCommand;
use Tempest\Container\Singleton;
use Tempest\Container\Stubs\InitializerStub;
use Tempest\Core\PublishesFiles;
use Tempest\Generation\ClassManipulator;
use Tempest\Generation\DataObjects\StubFile;

final class MakeInitializerCommand
{
    use PublishesFiles;

    #[ConsoleCommand(
        name: 'make:initializer',
        description: 'Creates a new initializer class',
        aliases: ['initializer:make', 'initializer:create', 'create:initializer'],
    )]
    public function __invoke(
        #[ConsoleArgument(description: 'The name of the initializer class to create')]
        string $className,
        #[ConsoleArgument(name: 'singleton', description: 'Whether the initializer should be a singleton')]
        bool $isSingleton = false,
    ): void {
        $suggestedPath = $this->getSuggestedPath($className);
        $targetPath = $this->promptTargetPath($suggestedPath);
        $shouldOverride = $this->askForOverride($targetPath);

        $this->stubFileGenerator->generateClassFile(
            stubFile: StubFile::from(InitializerStub::class),
            targetPath: $targetPath,
            shouldOverride: $shouldOverride,
            manipulations: [
                function (ClassManipulator $stubClass) use ($isSingleton) {
                    if ($isSingleton) {
                        $stubClass->addMethodAttribute('initialize', Singleton::class);
                    }

                    return $stubClass;
                },
            ],
        );

        $this->console->success(sprintf('Initializer successfully created at "%s".', $targetPath));
    }
}
