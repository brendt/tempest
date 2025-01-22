<?php

declare(strict_types=1);

namespace Tests\Tempest\Integration\Console\Components;

use Exception;
use Symfony\Component\Process\Process;
use Tempest\Console\Components\Interactive\TaskComponent;
use Tempest\Console\Console;
use Tempest\Console\Terminal\Terminal;
use Tempest\Database\Connections\SQLiteConnection;
use Tempest\Database\DatabaseConfig;
use Tests\Tempest\Integration\FrameworkIntegrationTestCase;

/**
 * @internal
 */
final class TaskComponentTest extends FrameworkIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('These tests require the pcntl extension, which is not available on Windows.');
        }

        if (! $this->container->get(DatabaseConfig::class)->connection instanceof SQLiteConnection) {
            $this->markTestSkipped('These tests duplicate PDO connections due to `pnctl_fork`, so they are skipped until the framework supports closing connections.');
        }
    }

    public function test_no_task(): void
    {
        $this->console->withoutPrompting()->call(function (Console $console): void {
            $terminal = new Terminal($console);
            $component = new TaskComponent('Task in progress');

            $frames = iterator_to_array($component->render($terminal));

            $this->assertStringContainsString('Task in progress', $frames[0]);
            $this->assertStringContainsString('Done.', $frames[0]);
        });
    }

    public function test_process_task(): void
    {
        $this->console->withoutPrompting()->call(function (Console $console): void {
            $terminal = new Terminal($console);
            $process = new Process(['echo', 'hello world']);
            $component = new TaskComponent('Task in progress', $process);

            $frames = iterator_to_array($component->render($terminal));

            $this->assertStringContainsString('Task in progress', $frames[0]);
            $this->assertStringContainsString('Done in', $frames[1]);
        });
    }

    public function test_successful_task(): void
    {
        $this->console->withoutPrompting()->call(function (Console $console): void {
            $terminal = new Terminal($console);
            $component = new TaskComponent('Task in progress', function (): void {});

            $frames = iterator_to_array($component->render($terminal));

            $this->assertStringContainsString('Task in progress', $frames[0]);
            $this->assertStringContainsString('Done in', $frames[1]);
        });
    }

    public function test_failing_task(): void
    {
        $this->console->withoutPrompting()->call(function (Console $console): void {
            $terminal = new Terminal($console);
            $component = new TaskComponent('Task in progress', function (): never {
                throw new Exception('Failure');
            });

            $frames = iterator_to_array($component->render($terminal));

            $this->assertStringContainsString('Task in progress', $frames[0]);
            $this->assertStringContainsString('An error occurred.', $frames[1]);
        });
    }
}
