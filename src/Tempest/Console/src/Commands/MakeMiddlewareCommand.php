<?php

declare(strict_types=1);

namespace Tempest\Console\Commands;

use InvalidArgumentException;
use Tempest\Console\ConsoleArgument;
use Tempest\Console\ConsoleCommand;
use Tempest\Console\Enums\MiddlewareType;
use Tempest\Console\Stubs\CommandBusMiddlewareStub;
use Tempest\Console\Stubs\ConsoleMiddlewareStub;
use Tempest\Console\Stubs\EventBusMiddlewareStub;
use Tempest\Console\Stubs\HttpMiddlewareStub;
use Tempest\Core\PublishesFiles;
use Tempest\Generation\DataObjects\StubFile;

final class MakeMiddlewareCommand
{
    use PublishesFiles;

    #[ConsoleCommand(
        name: 'make:middleware',
        description: 'Creates a new middleware class',
        aliases: ['middleware:make', 'middleware:create', 'create:middleware'],
    )]
    public function __invoke(
        #[ConsoleArgument(description: 'The name of the middleware class to create')]
        string $className,
        #[ConsoleArgument(name: 'type', description: 'The type of the middleware to create')]
        MiddlewareType $middlewareType,
    ): void {
        $stubFile = $this->getStubFileFromMiddlewareType($middlewareType);
        $suggestedPath = $this->getSuggestedPath($className);
        $targetPath = $this->promptTargetPath($suggestedPath);
        $shouldOverride = $this->askForOverride($targetPath);

        $this->stubFileGenerator->generateClassFile(
            stubFile: $stubFile,
            targetPath: $targetPath,
            shouldOverride: $shouldOverride,
        );

        $this->console->success(sprintf('Middleware successfully created at <file="%s"/>.', $targetPath));
    }

    private function getStubFileFromMiddlewareType(MiddlewareType $middlewareType): StubFile
    {
        return match ($middlewareType) {
            MiddlewareType::CONSOLE => StubFile::from(ConsoleMiddlewareStub::class),
            MiddlewareType::HTTP => StubFile::from(HttpMiddlewareStub::class),
            MiddlewareType::EVENT_BUS => StubFile::from(EventBusMiddlewareStub::class),
            MiddlewareType::COMMAND_BUS => StubFile::from(CommandBusMiddlewareStub::class), // @phpstan-ignore match.alwaysTrue (Because this is a guardrail for the future implementations)
            default => throw new InvalidArgumentException(sprintf('The "%s" middleware type has no supported stub file.', $middlewareType->value)),
        };
    }
}
