<?php

declare(strict_types=1);

namespace Tempest\Console\Testing;

use Closure;
use Exception;
use Fiber;
use PHPUnit\Framework\Assert;
use Tempest\Console\Actions\ExecuteConsoleCommand;
use Tempest\Console\Components\InteractiveComponentRenderer;
use Tempest\Console\Console;
use Tempest\Console\ConsoleCommand;
use Tempest\Console\Exceptions\ConsoleErrorHandler;
use Tempest\Console\ExitCode;
use Tempest\Console\GenericConsole;
use Tempest\Console\Input\ConsoleArgumentBag;
use Tempest\Console\Input\MemoryInputBuffer;
use Tempest\Console\InputBuffer;
use Tempest\Console\Key;
use Tempest\Console\Output\MemoryOutputBuffer;
use Tempest\Console\OutputBuffer;
use Tempest\Container\Container;
use Tempest\Core\AppConfig;
use Tempest\Highlight\Highlighter;
use Tempest\Reflection\MethodReflector;

final class ConsoleTester
{
    private (OutputBuffer&MemoryOutputBuffer)|null $output = null;

    private (InputBuffer&MemoryInputBuffer)|null $input = null;

    private ?InteractiveComponentRenderer $componentRenderer = null;

    private ?ExitCode $exitCode = null;

    private bool $withPrompting = true;

    public function __construct(
        private readonly Container $container,
    ) {
    }

    public function call(string|Closure|array $command): self
    {
        $clone = clone $this;

        $memoryOutputBuffer = new MemoryOutputBuffer();
        $clone->container->singleton(OutputBuffer::class, $memoryOutputBuffer);

        $memoryInputBuffer = new MemoryInputBuffer();
        $clone->container->singleton(InputBuffer::class, $memoryInputBuffer);

        $console = new GenericConsole(
            output: $memoryOutputBuffer,
            input: $memoryInputBuffer,
            highlighter: $clone->container->get(Highlighter::class, 'console'),
            executeConsoleCommand: $clone->container->get(ExecuteConsoleCommand::class),
            argumentBag: $clone->container->get(ConsoleArgumentBag::class),
        );

        if ($this->withPrompting === false) {
            $console->disablePrompting();
        }

        if ($this->componentRenderer !== null) {
            $console->setComponentRenderer($this->componentRenderer);
        }

        $clone->container->singleton(Console::class, $console);

        $appConfig = $this->container->get(AppConfig::class);
        $appConfig->errorHandlers[] = $clone->container->get(ConsoleErrorHandler::class);

        $clone->output = $memoryOutputBuffer;
        $clone->input = $memoryInputBuffer;

        if ($command instanceof Closure) {
            $fiber = new Fiber(function () use ($clone, $command, $console): void {
                $clone->exitCode = $command($console) ?? ExitCode::SUCCESS;
            });
        } else {
            if (is_string($command) && class_exists($command)) {
                $command = [$command, '__invoke'];
            }

            if (is_array($command) || class_exists($command)) {
                $handler = MethodReflector::fromParts(...$command);

                $attribute = $handler->getAttribute(ConsoleCommand::class);

                if ($attribute === null) {
                    throw new Exception("Could not resolve console command from {$command[0]}::{$command[1]}");
                }

                $attribute->setHandler($handler);

                $command = $attribute->getName();
            }

            $fiber = new Fiber(function () use ($command, $clone): void {
                $argumentBag = new ConsoleArgumentBag(['tempest', ...explode(' ', $command)]);

                $clone->container->singleton(ConsoleArgumentBag::class, $argumentBag);

                $clone->exitCode = ($this->container->get(ExecuteConsoleCommand::class))($argumentBag->getCommandName());
            });
        }

        $fiber->start();

        if ($clone->componentRenderer !== null) {
            $clone->input("\e[1;1R"); // Set cursor for interactive testing
        }

        return $clone;
    }

    public function complete(?string $command = null): self
    {
        if ($command) {
            $input = explode(' ', $command);

            $inputString = implode(' ', array_map(
                fn (string $item) => "--input=\"{$item}\"",
                $input
            ));
        } else {
            $inputString = '';
        }

        return $this->call("_complete --current=0 --input=\"./tempest\" {$inputString}");
    }

    public function input(int|string|Key $input): self
    {
        $this->output->clear();

        $this->input->add($input);

        return $this;
    }

    public function submit(int|string $input = ''): self
    {
        $input = (string)$input;

        $this->input($input . Key::ENTER->value);

        return $this;
    }

    public function print(): self
    {
        echo "OUTPUT:" . PHP_EOL;
        echo $this->output->asUnformattedString();

        return $this;
    }

    public function printFormatted(): self
    {
        echo $this->output->asFormattedString();

        return $this;
    }

    public function getBuffer(?callable $callback = null): array
    {
        $buffer = array_map('trim', $this->output->getBufferWithoutFormatting());

        $this->output->clear();

        if ($callback !== null) {
            return $callback($buffer);
        }

        return $buffer;
    }

    public function useInteractiveTerminal(): self
    {
        $this->componentRenderer = new InteractiveComponentRenderer();

        return $this;
    }

    public function assertSee(string $text): self
    {
        return $this->assertContains($text, ignoreAnsi: true);
    }

    public function assertNotSee(string $text): self
    {
        return $this->assertDoesNotContain($text, ignoreAnsi: true);
    }

    public function assertContains(string $text, bool $ignoreLineEndings = true, bool $ignoreAnsi = false): self
    {
        $method = $ignoreLineEndings ? 'assertStringContainsStringIgnoringLineEndings' : 'assertStringContainsString';
        $output = $ignoreAnsi
            ? preg_replace('/\x1b\[[0-9;]*m/', '', $this->output->asUnformattedString())
            : $this->output->asUnformattedString();

        Assert::$method(
            $text,
            $output,
            sprintf(
                'Failed to assert that console output included text: %s. These lines were printed: %s',
                $text,
                PHP_EOL . PHP_EOL . $this->output->asUnformattedString() . PHP_EOL,
            ),
        );

        return $this;
    }

    public function assertDoesNotContain(string $text, bool $ignoreAnsi = false): self
    {
        $output = $ignoreAnsi
            ? preg_replace('/\x1b\[[0-9;]*m/', '', $this->output->asUnformattedString())
            : $this->output->asUnformattedString();

        Assert::assertStringNotContainsString(
            $text,
            $output,
            sprintf(
                'Failed to assert that console output did not include text: %s. These lines were printed: %s',
                $text,
                PHP_EOL . PHP_EOL . $this->output->asUnformattedString() . PHP_EOL,
            ),
        );

        return $this;
    }

    public function assertContainsFormattedText(string $text): self
    {
        Assert::assertStringContainsString(
            $text,
            $this->output->asFormattedString(),
            sprintf(
                'Failed to assert that console output included formatted text: %s. These lines were printed: %s',
                $text,
                PHP_EOL . $this->output->asFormattedString(),
            ),
        );

        return $this;
    }

    public function assertJson(): self
    {
        Assert::assertJson($this->output->asUnformattedString());

        return $this;
    }

    public function assertExitCode(ExitCode $exitCode): self
    {
        Assert::assertNotNull($this->exitCode, "Expected {$exitCode->name}, but instead no exit code was set — maybe you missed providing some input?");

        Assert::assertSame($exitCode, $this->exitCode, "Expected the exit code to be {$exitCode->name}, instead got {$this->exitCode->name}");

        return $this;
    }

    public function assertSuccess(): self
    {
        $this->assertExitCode(ExitCode::SUCCESS);

        return $this;
    }

    public function assertError(): self
    {
        $this->assertExitCode(ExitCode::ERROR);

        return $this;
    }

    public function assertCancelled(): self
    {
        $this->assertExitCode(ExitCode::CANCELLED);

        return $this;
    }

    public function assertInvalid(): self
    {
        $this->assertExitCode(ExitCode::INVALID);

        return $this;
    }

    public function withoutPrompting(): self
    {
        $this->withPrompting = false;

        return $this;
    }

    public function withPrompting(): self
    {
        $this->withPrompting = true;

        return $this;
    }

    public function dd(): self
    {
        ld($this->output->asFormattedString());

        return $this;
    }
}
