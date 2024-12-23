<?php

declare(strict_types=1);

namespace Tempest\Console;

use BackedEnum;
use Closure;
use Stringable;
use Symfony\Component\Process\Process;
use Tempest\Console\Actions\ExecuteConsoleCommand;
use Tempest\Console\Components\Interactive\ConfirmComponent;
use Tempest\Console\Components\Interactive\MultipleChoiceComponent;
use Tempest\Console\Components\Interactive\PasswordComponent;
use Tempest\Console\Components\Interactive\ProgressBarComponent;
use Tempest\Console\Components\Interactive\SearchComponent;
use Tempest\Console\Components\Interactive\SingleChoiceComponent;
use Tempest\Console\Components\Interactive\TaskComponent;
use Tempest\Console\Components\Interactive\TextInputComponent;
use Tempest\Console\Components\InteractiveComponentRenderer;
use Tempest\Console\Components\Renderers\KeyValueRenderer;
use Tempest\Console\Components\Renderers\MessageRenderer;
use Tempest\Console\Exceptions\UnsupportedComponent;
use Tempest\Console\Highlight\TempestConsoleLanguage\TempestConsoleLanguage;
use Tempest\Console\Input\ConsoleArgumentBag;
use Tempest\Container\Tag;
use Tempest\Highlight\Highlighter;
use Tempest\Highlight\Language;
use Tempest\Support\ArrayHelper;
use Tempest\Support\Conditions\HasConditions;
use function Tempest\Support\arr;

final class GenericConsole implements Console
{
    use HasConditions;

    private ?string $label = null;

    private bool $isForced = false;

    private bool $supportsPrompting = true;

    private ?InteractiveComponentRenderer $componentRenderer = null;

    public function __construct(
        private readonly OutputBuffer $output,
        private readonly InputBuffer $input,
        #[Tag('console')]
        private readonly Highlighter $highlighter,
        private readonly ExecuteConsoleCommand $executeConsoleCommand,
        private readonly ConsoleArgumentBag $argumentBag,
    ) {
    }

    public function call(string|array $command, string|array $arguments = []): ExitCode|int
    {
        return ($this->executeConsoleCommand)($command, $arguments);
    }

    public function setComponentRenderer(InteractiveComponentRenderer $componentRenderer): self
    {
        $this->componentRenderer = $componentRenderer;

        return $this;
    }

    public function setForced(): self
    {
        $this->isForced = true;

        return $this;
    }

    public function disablePrompting(): self
    {
        $this->supportsPrompting = false;

        return $this;
    }

    public function read(int $bytes): string
    {
        if (! $this->supportsPrompting()) {
            return '';
        }

        return $this->input->read($bytes);
    }

    public function readln(): string
    {
        if (! $this->supportsPrompting()) {
            return '';
        }

        return $this->input->readln();
    }

    public function write(string $contents): static
    {
        $this->writeWithLanguage($contents, new TempestConsoleLanguage());

        return $this;
    }

    public function header(string $header, ?string $subheader = null): static
    {
        $this->writeln();
        $this->writeln("<style='dim fg-blue'>//</style> <style='bold fg-blue'>".mb_strtoupper($header).'</style>');

        if ($subheader) {
            $this->writeln($subheader);
        }

        return $this;
    }

    public function writeln(string $line = ''): static
    {
        $this->write($line . PHP_EOL);

        return $this;
    }

    public function writeWithLanguage(string $contents, Language $language): self
    {
        if ($this->label) {
            $contents = "<style='dim fg-blue'>//</style> <style='bold fg-blue'>" . $this->label . "\n{$contents}";
        }

        $this->output->write($this->highlighter->parse($contents, $language));

        return $this;
    }

    public function writeRaw(string $contents): self
    {
        $this->output->write($contents);

        return $this;
    }

    public function info(string $contents, ?string $title = null): self
    {
        $this->writeln((new MessageRenderer('𝓲', 'blue'))->render($contents));

        return $this;
    }

    public function error(string $contents, ?string $title = null): self
    {
        $this->writeln((new MessageRenderer('×', 'red'))->render($contents, $title));

        return $this;
    }

    public function warning(string $contents, ?string $title = null): self
    {
        $this->writeln((new MessageRenderer('⚠', 'yellow'))->render($contents, $title));

        return $this;
    }

    public function success(string $contents, ?string $title = null): self
    {
        $this->writeln((new MessageRenderer('✓', 'green'))->render($contents, $title));

        return $this;
    }

    public function withLabel(string $label): self
    {
        $clone = clone $this;

        $clone->label = $label;

        return $clone;
    }

    public function keyValue(string $key, ?string $value = null): self
    {
        $this->writeln((new KeyValueRenderer())->render($key, $value));

        return $this;
    }

    public function component(InteractiveConsoleComponent $component, array $validation = []): mixed
    {
        if ($this->componentRenderer !== null && $this->componentRenderer->isComponentSupported($this, $component)) {
            return $this->componentRenderer->render($this, $component, $validation);
        }

        if ($component instanceof HasStaticComponent) {
            return $component->staticComponent->render($this);
        }

        throw new UnsupportedComponent($component);
    }

    public function ask(
        string $question,
        null|array|ArrayHelper|string $options = null,
        mixed $default = null,
        bool $multiple = false,
        bool $multiline = false,
        ?string $placeholder = null,
        ?string $hint = null,
        array $validation = [],
    ): null|int|string|Stringable|array {
        if ($this->isForced && $default) {
            return $default;
        }

        if ($options instanceof ArrayHelper) {
            $options = $options->toArray();
        }

        if (is_a($options, BackedEnum::class, allow_string: true)) {
            $options = arr($options::cases())->mapWithKeys(
                fn (BackedEnum $enum) => yield $enum->value => $enum->name,
            )->toArray();
        }

        if ($default instanceof BackedEnum) {
            $default = $default->value;
        }

        $component = match (true) {
            $options === null || $options === [] => new TextInputComponent($question, $default, $placeholder, $hint, $multiline),
            $multiple => new MultipleChoiceComponent(
                label: $question,
                options: $options,
                default: ArrayHelper::wrap($default),
            ),
            default => new SingleChoiceComponent(
                label: $question,
                options: $options,
                default: $default,
            ),
        };

        return $this->component($component, $validation);
    }

    public function confirm(string $question, bool $default = false, ?string $yes = null, ?string $no = null): bool
    {
        if ($this->isForced) {
            return true;
        }

        return $this->component(new ConfirmComponent($question, $default, $yes, $no));
    }

    public function password(string $label = 'Password', bool $confirm = false, array $validation = []): ?string
    {
        if (! $confirm) {
            return $this->component(new PasswordComponent($label), $validation);
        }

        $password = null;
        $passwordConfirm = null;

        while ($password === null || $password !== $passwordConfirm) {
            if ($password !== null) {
                $this->error("Passwords don't match");
            }

            $password = $this->component(new PasswordComponent($label));
            $passwordConfirm = $this->component(new PasswordComponent('Please confirm'));
        }

        return $password;
    }

    public function progressBar(iterable $data, Closure $handler): array
    {
        return $this->component(new ProgressBarComponent($data, $handler));
    }

    public function task(string $label, null|Process|Closure $handler = null): bool
    {
        return $this->component(new TaskComponent($label, $handler));
    }

    public function search(string $label, Closure $search, bool $multiple = false, null|string|array $default = null): mixed
    {
        return $this->component(new SearchComponent($label, $search, $multiple, $default));
    }

    public function supportsPrompting(): bool
    {
        if ($this->supportsPrompting === false) {
            return false;
        }

        if ($this->argumentBag->get('interaction')?->value === false) {
            return false;
        }

        return true;
    }
}
