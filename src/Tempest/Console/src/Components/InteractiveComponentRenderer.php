<?php

declare(strict_types=1);

namespace Tempest\Console\Components;

use Fiber;
use Tempest\Console\Console;
use Tempest\Console\Exceptions\InterruptException;
use Tempest\Console\HandlesKey;
use Tempest\Console\InteractiveConsoleComponent;
use Tempest\Console\Key;
use Tempest\Console\Terminal\Terminal;
use Tempest\Reflection\ClassReflector;
use Tempest\Reflection\MethodReflector;
use Tempest\Validation\Exceptions\InvalidValueException;
use Tempest\Validation\Rule;
use Tempest\Validation\Validator;
use function Tempest\Support\arr;

final class InteractiveComponentRenderer
{
    private array $afterRenderCallbacks = [];

    private array $validationErrors = [];

    private bool $shouldRerender = true;

    public function render(Console $console, InteractiveConsoleComponent $component, array $validation = []): mixed
    {
        $clone = clone $this;

        return $clone->renderComponent($console, $component, $validation);
    }

    private function renderComponent(Console $console, InteractiveConsoleComponent $component, array $validation = []): mixed
    {
        $terminal = $this->createTerminal($console);

        $fibers = [
            new Fiber(fn () => $this->applyKey($component, $console, $validation)),
            new Fiber(fn () => $this->renderFrames($component, $terminal)),
        ];

        try {
            while ($fibers !== []) {
                foreach ($fibers as $key => $fiber) {
                    if (! $fiber->isStarted()) {
                        $fiber->start();
                    }

                    $fiber->resume();

                    if ($fiber->isTerminated()) {
                        unset($fibers[$key]);

                        if (! is_null($return = $fiber->getReturn())) {
                            return $return;
                        }
                    }
                }

                // If we're running within a fiber, we'll suspend here as well so that the parent can continue
                // This is needed for our testing helper
                if (Fiber::getCurrent() !== null) {
                    Fiber::suspend();
                }
            }
        } finally {
            $this->closeTerminal($terminal);
        }

        return null;
    }

    private function applyKey(InteractiveConsoleComponent $component, Console $console, array $validation): mixed
    {
        [$keyBindings, $inputHandlers] = $this->resolveHandlers($component);

        while (true) {
            while ($callback = array_shift($this->afterRenderCallbacks)) {
                $callback($component);
            }

            usleep(50);
            $key = $console->read(16);

            // If there's no keypress, continue
            if ($key === '') {
                Fiber::suspend();

                continue;
            }

            if ($component->getState() === ComponentState::BLOCKED) {
                $this->shouldRerender = true;

                continue;
            }

            /** @var MethodReflector[] $handlersForKey */
            $handlersForKey = $keyBindings[$key] ?? [];

            // If we have multiple handlers, we put the ones that return nothing
            // first because the ones that return something will be overridden otherwise.
            usort($handlersForKey, fn (MethodReflector $a, MethodReflector $b) => $b->getReturnType()->equals('void') <=> $a->getReturnType()->equals('void'));

            // CTRL+C and CTRL+D means we exit the CLI, but only if there is no custom
            // handler. When we exit, we want one last render to display pretty
            // styles, so we will throw the exception in the next loop.
            if ($handlersForKey === [] && ($key === Key::CTRL_C->value || $key === Key::CTRL_D->value)) {
                $component->setState(ComponentState::CANCELLED);
                $this->afterRenderCallbacks[] = fn () => throw new InterruptException();
                $this->shouldRerender = true;
                Fiber::suspend();

                continue;
            }

            $this->shouldRerender = true;

            $return = null;

            // If we have handlers for that key, apply them.
            foreach ($handlersForKey as $handler) {
                $return ??= $handler->invokeArgs($component);
            }

            // If we didn't have any handler for the key,
            // we call catch-all handlers.
            if ($handlersForKey === []) {
                foreach ($inputHandlers as $handler) {
                    $return ??= $handler->invokeArgs($component, [$key]);
                }
            }

            // If nothing's returned, we can continue waiting for the next key press
            if ($return === null) {
                Fiber::suspend();

                continue;
            }

            // If something's returned, we'll need to validate the result
            $this->validationErrors = [];

            $failingRule = $this->validate($return, $validation);

            // If invalid, we'll remember the validation message and continue
            if ($failingRule !== null) {
                $this->validationErrors[] = $failingRule->message();
                Fiber::suspend();

                continue;
            }

            Fiber::suspend();

            // If valid, we can return
            return $return;
        }
    }

    private function renderFrames(InteractiveConsoleComponent $component, Terminal $terminal): mixed
    {
        while (true) {
            usleep(100);

            // If there are no updates,
            // we won't spend time re-rendering the same frame
            if (! $this->shouldRerender) {
                Fiber::suspend();

                continue;
            }

            // Rerender the frames, it could be one or more
            $frames = $terminal->render(
                component: $component,
                validationErrors: $this->validationErrors,
            );

            // Looping over the frames will display them
            // (this happens within the Terminal class, might need to refactor)
            // We suspend between each frame to allow key press interruptions
            foreach ($frames as $frame) {
                Fiber::suspend();
            }

            $return = $frames->getReturn();

            // Everything's rerendered
            $this->shouldRerender = false;

            if ($return !== null) {
                return $return;
            }
        }
    }

    private function resolveHandlers(InteractiveConsoleComponent $component): array
    {
        /** @var \Tempest\Reflection\MethodReflector[][] $keyBindings */
        $keyBindings = [];

        $inputHandlers = [];

        foreach (new ClassReflector($component)->getPublicMethods() as $method) {
            foreach ($method->getAttributes(HandlesKey::class) as $handlesKey) {
                if ($handlesKey->key === null) {
                    $inputHandlers[] = $method;
                } else {
                    $keyBindings[$handlesKey->key->value][] = $method;
                }
            }
        }

        return [$keyBindings, $inputHandlers];
    }

    /**
     * @param \Tempest\Validation\Rule[] $validation
     */
    private function validate(mixed $value, array $validation): ?Rule
    {
        $validator = new Validator();

        try {
            $validator->validateValue($value, $validation);
        } catch (InvalidValueException $invalidValueException) {
            return $invalidValueException->failingRules[0];
        }

        return null;
    }

    public function isComponentSupported(Console $console, InteractiveConsoleComponent $component): bool
    {
        if (! arr($component->extensions ?? [])->every(fn (string $ext) => extension_loaded($ext))) {
            return false;
        }

        if (! new Terminal($console)->supportsTty()) {
            return false;
        }

        return true;
    }

    private function createTerminal(Console $console): Terminal
    {
        $terminal = new Terminal($console);
        $terminal->switchToInteractiveMode();

        $terminal->cursor->clearAfter();
        stream_set_blocking(STDIN, false);

        return $terminal;
    }

    private function closeTerminal(Terminal $terminal): void
    {
        $terminal->placeCursorToEnd();
        $terminal->switchToNormalMode();
        stream_set_blocking(STDIN, true);
    }
}
