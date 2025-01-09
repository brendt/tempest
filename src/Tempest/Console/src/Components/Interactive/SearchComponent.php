<?php

declare(strict_types=1);

namespace Tempest\Console\Components\Interactive;

use Closure;
use Tempest\Console\Components\Concerns\HasErrors;
use Tempest\Console\Components\Concerns\HasState;
use Tempest\Console\Components\Concerns\HasTextBuffer;
use Tempest\Console\Components\Concerns\RendersControls;
use Tempest\Console\Components\OptionCollection;
use Tempest\Console\Components\Renderers\ChoiceRenderer;
use Tempest\Console\Components\Static\StaticSearchComponent;
use Tempest\Console\Components\TextBuffer;
use Tempest\Console\HandlesKey;
use Tempest\Console\HasCursor;
use Tempest\Console\HasStaticComponent;
use Tempest\Console\InteractiveConsoleComponent;
use Tempest\Console\Key;
use Tempest\Console\Point;
use Tempest\Console\StaticConsoleComponent;
use Tempest\Console\Terminal\Terminal;
use Tempest\Support\ArrayHelper;

final class SearchComponent implements InteractiveConsoleComponent, HasCursor, HasStaticComponent
{
    use HasErrors;
    use HasState;
    use HasTextBuffer;
    use RendersControls;

    private ChoiceRenderer $renderer;

    public OptionCollection $options;

    private ?string $previousQuery = null;

    public function __construct(
        public string $label,
        public Closure $search,
        public bool $multiple = false,
        public null|array|string $default = null,
    ) {
        $this->bufferEnabled = ! $this->multiple;
        $this->buffer = new TextBuffer();
        $this->renderer = new ChoiceRenderer(default: (string) $default, multiple: $multiple);
        $this->options = new OptionCollection([]);

        if ($this->multiple) {
            $this->default = ArrayHelper::wrap($this->default);
        }

        $this->updateQuery();
    }

    public StaticConsoleComponent $staticComponent {
        get => new StaticSearchComponent(
            label: $this->label,
            search: $this->search,
            multiple: $this->multiple,
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
            filtering: $this->bufferEnabled,
            options: $this->options,
        );
    }

    private function updateQuery(): void
    {
        if ($this->previousQuery === $this->buffer->text) {
            return;
        }

        $this->options->setCollection(array_values(($this->search)($this->buffer->text)));
        $this->previousQuery = $this->buffer->text;
    }

    private function getControls(): array
    {
        $controls = [];

        if ($this->multiple && $this->bufferEnabled) {
            $controls['esc'] = 'select';
        } elseif ($this->multiple) {
            $controls['/'] = 'filter';
            $controls['space'] = 'select';
        }

        return [
            ...$controls,
            '↑' => 'up',
            '↓' => 'down',
            'enter' => $this->multiple && $this->default && $this->options->getSelectedOptions() === []
                ? 'skip'
                : 'confirm',
            'ctrl+c' => 'cancel',
        ];
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
        if ($this->multiple) {
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
        }

        $this->buffer->input($key);
        $this->updateQuery();
    }

    #[HandlesKey(Key::ENTER)]
    public function enter(): mixed
    {
        if ($this->multiple) {
            return $this->options->getRawSelectedOptions() ?: $this->default;
        }

        if (($active = $this->options->getActive()) !== null) {
            return $active->value;
        }

        return null;
    }

    #[HandlesKey(Key::ESCAPE)]
    public function stopFiltering(): void
    {
        if (! $this->multiple) {
            return;
        }

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
