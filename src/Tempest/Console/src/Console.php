<?php

declare(strict_types=1);

namespace Tempest\Console;

use BackedEnum;
use Closure;
use Stringable;
use Symfony\Component\Process\Process;
use Tempest\Highlight\Language;
use Tempest\Support\ArrayHelper;

interface Console
{
    public function call(string|array $command, string|array $arguments = []): ExitCode|int;

    public function readln(): string;

    public function read(int $bytes): string;

    public function write(string $contents): self;

    public function writeln(string $line = ''): self;

    public function writeRaw(string $contents): self;

    public function writeWithLanguage(string $contents, Language $language): self;

    /**
     * @param \Tempest\Validation\Rule[] $validation
     */
    public function component(InteractiveConsoleComponent $component, array $validation = []): mixed;

    /**
     * @param null|array|ArrayHelper|class-string<BackedEnum> $options
     * @param mixed|null $default
     * @param \Tempest\Validation\Rule[] $validation
     */
    public function ask(
        string $question,
        null|array|ArrayHelper|string $options = null,
        mixed $default = null,
        bool $multiple = false,
        bool $multiline = false,
        ?string $placeholder = null,
        ?string $hint = null,
        array $validation = [],
    ): null|int|string|Stringable|array;

    public function confirm(string $question, bool $default = false, ?string $yes = null, ?string $no = null): bool;

    public function password(string $label = 'Password', bool $confirm = false, array $validation = []): ?string;

    public function progressBar(iterable $data, Closure $handler): array;

    /**
     * @param Closure(string $search): array $search
     */
    public function search(string $label, Closure $search, bool $multiple = false, null|string|array $default = null): mixed;

    public function task(string $label, null|Process|Closure $handler): bool;

    public function header(string $header, ?string $subheader = null): self;

    public function info(string $line, ?string $symbol = null): self;

    public function error(string $line, ?string $symbol = null): self;

    public function warning(string $line, ?string $symbol = null): self;

    public function success(string $line, ?string $symbol = null): self;

    /**
     * @param mixed|Closure(self): bool $condition
     * @param Closure(self): self $callback
     */
    public function when(mixed $condition, Closure $callback): self;

    /**
     * @param mixed|Closure(self): bool $condition
     * @param Closure(self): self $callback
    */
    public function unless(mixed $condition, Closure $callback): self;

    public function withLabel(string $label): self;

    public function supportsPrompting(): bool;

    public function disablePrompting(): self;
}
