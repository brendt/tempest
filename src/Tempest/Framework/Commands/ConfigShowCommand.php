<?php

declare(strict_types=1);

namespace Tempest\Framework\Commands;

use Tempest\Console\ConsoleArgument;
use Tempest\Console\ConsoleCommand;
use Tempest\Console\ExitCode;
use Tempest\Console\HasConsole;
use Tempest\Core\Kernel\LoadConfig;
use Tempest\Highlight\Languages\Json\JsonLanguage;
use Tempest\Highlight\Languages\Php\PhpLanguage;
use Tempest\Reflection\ClassReflector;
use function file_get_contents;
use function function_exists;
use function is_array;
use function is_object;
use function realpath;
use function str_contains;
use function var_export;

final readonly class ConfigShowCommand
{
    use HasConsole;

    private const int MAX_JSON_DEPTH = 32;

    public function __construct(
        private LoadConfig $loadConfig,
    ) {
    }

    #[ConsoleCommand(name: 'config:show', description: 'Shows resolved configuration files', aliases: ['config'])]
    public function __invoke(
        #[ConsoleArgument(description: 'Specifies the format in which the configuration should be printed.')]
        ConfigShowFormat $format = ConfigShowFormat::PRETTY,
        #[ConsoleArgument(description: 'Searches configuration files interactively.')]
        ?bool $search = false,
        #[ConsoleArgument(description: 'Filters the configuration files by the given string.')]
        ?string $filter = null,
    ): ExitCode {
        $configs = $this->resolveConfig($filter, $search);

        if (empty($configs)) {
            $this->console->error('No configuration found.');

            return ExitCode::ERROR;
        }

        match ($format) {
            ConfigShowFormat::DUMP => $this->dump($configs),
            ConfigShowFormat::PRETTY => $this->pretty($configs),
            ConfigShowFormat::FILE => $this->file($configs),
        };

        return ExitCode::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveConfig(?string $filter, bool $search): array
    {
        $configPaths = $this->loadConfig->find();
        $configs = [];
        $uniqueMap = [];

        foreach ($configPaths as $configPath) {
            $config = require $configPath;
            $configPath = realpath($configPath);

            if (
                $filter === null
                || str_contains($configPath, $filter)
                || str_contains($config::class, $filter)
            ) {
                $configs[$configPath] = $config;
                $uniqueMap[$config::class] = $configPath;
            }
        }

        // LoadConfig::find() returns all config paths
        // that are overwritten by container in their order
        $resolvedConfigs = [];

        foreach ($uniqueMap as $configPath) {
            $resolvedConfigs[$configPath] = $configs[$configPath];
        }

        if (! $search) {
            return $resolvedConfigs;
        }

        $selectedPath = $this->search($resolvedConfigs);

        return [$selectedPath => $resolvedConfigs[$selectedPath]];
    }

    /**
     * @param array<string, mixed> $configs
     */
    private function search(array $configs): string
    {
        $data = array_keys($configs);
        sort($data);

        $return = $this->console->search(
            label: 'Which configuration file would you like to view?',
            search: function (string $query) use ($data): array {
                if ($query === '') {
                    return $data;
                }

                return array_filter(
                    array: $data,
                    callback: fn (string $path) => str_contains($path, $query),
                );
            },
            default: $data[0],
        );

        $this->console->writeln();

        return $return;
    }

    /**
     * @param array<string, mixed> $configs
     */
    private function dump(array $configs): void
    {
        if (function_exists('lw')) {
            lw($configs);

            return;
        }

        $this->console->writeln(var_export($configs, true));
    }

    /**
     * @param array<string, mixed> $configs
     */
    private function pretty(array $configs): void
    {
        $formatted = $this->formatForJson($configs);

        $this->console->writeWithLanguage(
            json_encode(
                $formatted,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            ),
            new JsonLanguage(),
        );
    }

    private function formatForJson(mixed $value, int $depth = 0): mixed
    {
        if ($depth > self::MAX_JSON_DEPTH) {
            return '@...';
        }

        if (is_object($value)) {
            $result = [
                '@type' => $value::class,
            ];

            $reflector = new ClassReflector($value);

            foreach ($reflector->getProperties() as $property) {
                $result[$property->getName()] = $this->formatForJson($property->getValue($value), $depth + 1);
            }

            return $result;
        }

        if (is_array($value)) {
            return array_map(fn ($item) => $this->formatForJson($item, $depth + 1), $value);
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $configs
     */
    private function file(array $configs): void
    {
        $phpLanguage = new PhpLanguage();

        foreach (array_keys($configs) as $path) {
            $this->console->writeln("<em>{$path}</em>");
            $this->console->writeWithLanguage(
                file_get_contents($path),
                $phpLanguage,
            );
            $this->console->writeln();
        }
    }
}
