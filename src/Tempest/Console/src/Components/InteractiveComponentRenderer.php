<?php

declare(strict_types=1);

namespace Tempest\Console\Components;

use Fiber;
use Tempest\Console\Console;
use Tempest\Console\Exceptions\InterruptException;
use Tempest\Console\HandlesInterruptions;
use Tempest\Console\HandlesKey;
use Tempest\Console\InteractiveConsoleComponent;
use Tempest\Console\Key;
use Tempest\Console\Terminal\Terminal;
use Tempest\Reflection\ClassReflector;
use Tempest\Reflection\MethodReflector;
use Tempest\Validation\Exceptions\InvalidValueException;
use Tempest\Validation\Rule;
use Tempest\Validation\Validator;

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
            // Render a last time the component,
            // to display its proper state.
            $render = match ($component->getState()) {
                State::CANCELLED,
                State::SUBMITTED => $terminal->render($component)->current(),
                default => null
            };

            $this->closeTerminal($terminal);

            if (! is_null($render)) {
                return $render;
            }
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

            if ($component->getState() === State::BLOCKED) {
                $this->shouldRerender = true;

                continue;
            }

            /** @var MethodReflector[] $handlersForKey */
            $handlersForKey = $keyBindings[$key] ?? [];

            // If we have multiple handlers, we put the ones that return nothing
            // first because the ones that return something will be overriden otherwise.
            usort($handlersForKey, fn (MethodReflector $a, MethodReflector $b) => $b->getReturnType()->equals('void') <=> $a->getReturnType()->equals('void'));

            // If we pressed CTRL+C or CTRL+D, we want to exit.
            // However, if we overriden one of those handler, we don't leave.
            if ($handlersForKey === [] && ($key === Key::CTRL_C->value || $key === Key::CTRL_D->value)) {
                // Components marked as `HandlesInterruptions` support being re-rendered
                // before closing the cterminal, so we'll throw in the next iteration.
                if ($component instanceof HandlesInterruptions) {
                    $component->setState(State::CANCELLED);
                    $this->afterRenderCallbacks[] = fn () => throw new InterruptException();

                    continue;
                }

                throw new InterruptException();
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

        foreach ((new ClassReflector($component))->getPublicMethods() as $method) {
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

    private function createTerminal(Console $console): Terminal
    {
        $terminal = new Terminal($console);
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
