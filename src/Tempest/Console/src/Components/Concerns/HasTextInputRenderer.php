<?php

declare(strict_types=1);

namespace Tempest\Console\Components\Concerns;

use Tempest\Console\Components\Renderers\TextInputRenderer;
use Tempest\Console\HandlesKey;
use Tempest\Console\InteractiveConsoleComponent;
use Tempest\Console\Key;

/**
 * @mixin InteractiveConsoleComponent
 * @phpstan-require-implements InteractiveConsoleComponent
 */
trait HasTextInputRenderer
{
    use HasState;
    use HasTextBuffer;

    public TextInputRenderer $renderer;

    public bool $multiline = false;

    #[HandlesKey(Key::ENTER)]
    public function enter(): ?string
    {
        if ($this->multiline) {
            $this->buffer->input("\n");

            return null;
        }

        return $this->buffer->text ?? '';
    }

    #[HandlesKey(Key::ALT_ENTER)]
    public function altEnter(): ?string
    {
        if (! $this->multiline) {
            return null;
        }

        return $this->buffer->text ?? '';
    }

    #[HandlesKey(Key::UP)]
    public function up(): void
    {
        if (! $this->multiline) {
            $this->buffer->moveCursorToStartOfLine();

            return;
        }

        $this->buffer->moveCursorY(-1);
    }

    #[HandlesKey(Key::DOWN)]
    public function down(): void
    {
        if (! $this->multiline) {
            $this->buffer->moveCursorToEndOfLine();

            return;
        }

        $this->buffer->moveCursorY(1);
    }

    #[HandlesKey]
    public function input(string $key): void
    {
        if (in_array($key, ["\n", "\r\n"], strict: true) && ! $this->multiline) {
            return;
        }

        $this->buffer->input($key);
    }
}
