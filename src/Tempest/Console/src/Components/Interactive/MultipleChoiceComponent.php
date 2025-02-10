<?php

declare(strict_types=1);

namespace Tempest\Console\Components\Interactive;

use Tempest\Console\Components\Concerns\HasErrors;
use Tempest\Console\Components\Concerns\HasState;
use Tempest\Console\Components\Concerns\HasTextBuffer;
use Tempest\Console\Components\Concerns\RendersControls;
use Tempest\Console\Components\OptionCollection;
use Tempest\Console\Components\Renderers\ChoiceRenderer;
use Tempest\Console\Components\Static\StaticMultipleChoiceComponent;
use Tempest\Console\Components\TextBuffer;
use Tempest\Console\HandlesKey;
use Tempest\Console\HasCursor;
use Tempest\Console\HasStaticComponent;
use Tempest\Console\InteractiveConsoleComponent;
use Tempest\Console\Key;
use Tempest\Console\Point;
use Tempest\Console\StaticConsoleComponent;
use Tempest\Console\Terminal\Terminal;

final class MultipleChoiceComponent implements InteractiveConsoleComponent, HasCursor, HasStaticComponent
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
        public array $default = [],
    ) {
        $this->bufferEnabled = false;
        $this->options = new OptionCollection($options);
        $this->buffer = new TextBuffer();
        $this->renderer = new ChoiceRenderer(multiple: true);
        $this->updateQuery();
    }

    public StaticConsoleComponent $staticComponent {
        get => new StaticMultipleChoiceComponent(
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
        return [
            ...($this->bufferEnabled ? [
                'esc' => 'select',
            ] : [
                '/' => 'filter',
                'space' => 'select',
            ]),
            '↑' => 'up',
            '↓' => 'down',
            'enter' => $this->options->getSelectedOptions() === []
                ? 'skip'
                : 'confirm',
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
            $this->updateQuery();

            return;
        }

        if (! $this->bufferEnabled) {
            match (mb_strtolower($key)) {
                ' ' => $this->options->toggleCurrent(),
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

    #[HandlesKey(Key::ESCAPE)]
    public function stopFiltering(): void
    {
        $this->bufferEnabled = false;
    }

    #[HandlesKey(Key::ENTER)]
    public function enter(): array
    {
        return $this->options->getRawSelectedOptions($this->default);
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
