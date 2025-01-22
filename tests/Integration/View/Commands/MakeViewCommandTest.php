<?php

declare(strict_types=1);

namespace Tests\Tempest\Integration\Console\Commands;

use PHPUnit\Framework\Attributes\Test;
use Tempest\Core\ComposerNamespace;
use Tests\Tempest\Integration\FrameworkIntegrationTestCase;

/**
 * @internal
 */
final class MakeViewCommandTest extends FrameworkIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->installer->configure(
            __DIR__ . '/install',
            new ComposerNamespace('App\\', __DIR__ . '/install/App'),
        );
    }

    protected function tearDown(): void
    {
        $this->installer->clean();

        parent::tearDown();
    }

    #[Test]
    public function make_view(): void
    {
        $this->console
            ->call('make:view home')
            ->submit();

        $this->installer->assertFileExists('App/home.view.php');

        $this->console
            ->call('make:view HomeView class')
            ->submit();

        $filepath = 'App/HomeView.php';
        $this->installer
            ->assertFileExists($filepath)
            ->assertFileContains($filepath, 'implements View')
            ->assertFileContains($filepath, "use Tempest\View\View")
            ->assertFileContains($filepath, 'use IsView')
            ->assertFileContains($filepath, "use Tempest\View\IsView");
    }
}
