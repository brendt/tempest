<?php

declare(strict_types=1);

namespace Tempest\Console\Components\Interactive;

use Stringable;
use Tempest\Console\Components\Concerns\HasErrors;
use Tempest\Console\Components\Concerns\HasState;
use Tempest\Console\Components\Concerns\HasTextBuffer;
use Tempest\Console\Components\Concerns\RendersControls;
use Tempest\Console\Components\OptionCollection;
use Tempest\Console\Components\Renderers\ChoiceRenderer;
use Tempest\Console\Components\Static\StaticSingleChoiceComponent;
use Tempest\Console\Components\TextBuffer;
use Tempest\Console\HandlesKey;
use Tempest\Console\HasCursor;
use Tempest\Console\HasStaticComponent;
use Tempest\Console\InteractiveConsoleComponent;
use Tempest\Console\Key;
use Tempest\Console\Point;
use Tempest\Console\StaticConsoleComponent;
use Tempest\Console\Terminal\Terminal;
use UnitEnum;

final class SingleChoiceComponent implements InteractiveConsoleComponent, HasCursor, HasStaticComponent
{
    use HasErrors;
    use HasState;
    use HasTextBuffer;
    use RendersControls;

    private ChoiceRenderer $renderer;

    private OptionCollection $options;

    public function __construct(
        public string $label,
        iterable $options,
        public null|int|UnitEnum|string $default = null,
    ) {
        $this->bufferEnabled = false;
        $this->options = new OptionCollection($options);
        $this->buffer = new TextBuffer();
        $this->renderer = new ChoiceRenderer(default: $default, multiple: false);
        $this->updateQuery();
    }

    public StaticConsoleComponent $staticComponent {
        get => new StaticSingleChoiceComponent(
            label: $this->label,
            options: $this->options->getRawOptions(),
            default: $this->default,
        );
    }

    public function render(Terminal $terminal): string
    {
        $this->updateQuery();

        return $this->renderer->render(
            terminal: $terminal,
            state: $this->state,
            label: $this->label,
            query: $this->buffer,
            options: $this->options,
            filtering: $this->bufferEnabled,
            placeholder: 'Filter...',
        );
    }

    private function getControls(): array
    {
        $controls = [];

        if ($this->bufferEnabled) {
            $controls['esc'] = 'select';
        } else {
            $controls['/'] = 'filter';
            $controls['space'] = 'select';
        }

        if ($this->default) {
            $controls['alt+enter'] = 'default';
        }

        return [
            ...$controls,
            '↑' => 'up',
            '↓' => 'down',
            'enter' => 'confirm',
            'ctrl+c' => 'cancel',
        ];
    }

    private function updateQuery(): void
    {
        $this->options->filter($this->buffer->text);
    }

    public function getCursorPosition(Terminal $terminal): Point
    {
        return $this->renderer->getCursorPosition($terminal, $this->buffer);
    }

    public function cursorVisible(): bool
    {
        return $this->bufferEnabled;
    }

    #[HandlesKey]
    public function input(string $key): void
    {
        if (! $this->bufferEnabled && $key === '/') {
            $this->bufferEnabled = true;

            return;
        }

        if (! $this->bufferEnabled) {
            match (mb_strtolower($key)) {
                'h', 'k' => $this->options->previous(),
                'j', 'l' => $this->options->next(),
                default => null,
            };
            $this->updateQuery();

            return;
        }

        $this->buffer->input($key);
        $this->updateQuery();
    }

    #[HandlesKey(Key::ENTER)]
    public function enter(): null|int|string|Stringable|UnitEnum
    {
        return $this->options->getRawActiveOption($this->default);
    }

    #[HandlesKey(Key::ALT_ENTER)]
    public function altEnter(): null|int|string|Stringable|UnitEnum
    {
        return $this->default;
    }

    #[HandlesKey(Key::ESCAPE)]
    public function stopFiltering(): void
    {
        $this->bufferEnabled = false;
    }

    #[HandlesKey(Key::UP)]
    #[HandlesKey(Key::HOME)]
    #[HandlesKey(Key::START_OF_LINE)]
    public function up(): void
    {
        $this->options->previous();
    }

    #[HandlesKey(Key::DOWN)]
    #[HandlesKey(Key::END)]
    #[HandlesKey(Key::END_OF_LINE)]
    public function down(): void
    {
        $this->options->next();
    }
}
