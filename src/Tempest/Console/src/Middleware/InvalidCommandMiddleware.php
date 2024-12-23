<?php

declare(strict_types=1);

namespace Tempest\Console\Middleware;

use BackedEnum;
use Tempest\Console\Actions\ExecuteConsoleCommand;
use Tempest\Console\Console;
use Tempest\Console\ConsoleMiddleware;
use Tempest\Console\ConsoleMiddlewareCallable;
use Tempest\Console\Exceptions\InvalidCommandException;
use Tempest\Console\ExitCode;
use Tempest\Console\Initializers\Invocation;
use Tempest\Console\Input\ConsoleInputArgument;
use Tempest\Validation\Rules\Boolean;
use Tempest\Validation\Rules\Enum;
use Tempest\Validation\Rules\NotEmpty;
use Tempest\Validation\Rules\Numeric;
use function Tempest\Support\str;

final readonly class InvalidCommandMiddleware implements ConsoleMiddleware
{
    public function __construct(
        private Console $console,
        private ExecuteConsoleCommand $executeConsoleCommand,
    ) {
    }

    public function __invoke(Invocation $invocation, ConsoleMiddlewareCallable $next): ExitCode|int
    {
        try {
            return $next($invocation);
        } catch (InvalidCommandException $invalidCommandException) {
            return $this->retry($invocation, $invalidCommandException);
        }
    }

    private function retry(Invocation $invocation, InvalidCommandException $exception): ExitCode|int
    {
        $this->console->header(
            header: $invocation->consoleCommand->getName(),
            subheader: $invocation->consoleCommand->description,
        );

        foreach ($exception->invalidArguments as $argument) {
            $isEnum = is_a($argument->type, BackedEnum::class, allow_string: true);

            $value = $this->console->ask(
                question: str($argument->name)->snake(' ')->upperFirst()->toString(),
                options: $isEnum ? $argument->type::cases() : null,
                default: $argument->default,
                hint: $argument->help ?? $argument->description,
                validation: array_filter([
                    $isEnum
                        ? new Enum($argument->type)
                        : new NotEmpty(),
                    match ($argument->type) {
                        'bool' => new Boolean(),
                        'int' => new Numeric(),
                        default => null,
                    },
                ]),
            );

            $invocation->argumentBag->add(new ConsoleInputArgument(
                name: $argument->name,
                position: $argument->position,
                value: $value,
            ));
        }

        return ($this->executeConsoleCommand)($invocation->consoleCommand->getName());
    }
}
