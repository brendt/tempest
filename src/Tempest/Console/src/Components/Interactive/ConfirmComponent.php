<?php

declare(strict_types=1);

namespace Tempest\Console\Components\Interactive;

use Tempest\Console\Components\Concerns\HasErrors;
use Tempest\Console\Components\Concerns\HasState;
use Tempest\Console\Components\Concerns\RendersControls;
use Tempest\Console\Components\Renderers\ConfirmRenderer;
use Tempest\Console\Components\Static\StaticConfirmComponent;
use Tempest\Console\HandlesKey;
use Tempest\Console\HasStaticComponent;
use Tempest\Console\InteractiveConsoleComponent;
use Tempest\Console\Key;
use Tempest\Console\StaticConsoleComponent;
use Tempest\Console\Terminal\Terminal;

final class ConfirmComponent implements InteractiveConsoleComponent, HasStaticComponent
{
    use HasErrors;
    use HasState;
    use RendersControls;

    private bool $answer;

    public ConfirmRenderer $renderer;

    public function __construct(
        private readonly string $question,
        private readonly bool $default = false,
        readonly ?string $yes = null,
        readonly ?string $no = null,
    ) {
        $this->answer = $default;
        $this->renderer = new ConfirmRenderer($yes ?? 'Yes', $no ?? 'No');
    }

    public StaticConsoleComponent $staticComponent {
        get => new StaticConfirmComponent(
            $this->question,
            $this->default,
        );
    }

    public function render(Terminal $terminal): string
    {
        return $this->renderer->render(
            terminal: $terminal,
            state: $this->state,
            answer: $this->answer,
            label: $this->question,
        );
    }

    private function getControls(): array
    {
        return [
            '↔' => 'toggle',
            'enter' => 'confirm',
        ];
    }

    #[HandlesKey(Key::DOWN)]
    #[HandlesKey(Key::UP)]
    #[HandlesKey(Key::LEFT)]
    #[HandlesKey(Key::RIGHT)]
    public function toggle(): void
    {
        $this->answer = ! $this->answer;
    }

    #[HandlesKey(Key::ENTER)]
    public function enter(): bool
    {
        return $this->answer;
    }

    #[HandlesKey]
    public function input(string $key): void
    {
        $this->answer = match (mb_strtolower($key)) {
            'y', 'o' => true,
            'n' => false,
            'h', 'j', 'k', 'l' => ! $this->answer,
            default => $this->answer,
        };
    }
}
