<?php

declare(strict_types=1);

namespace Tests\Tempest\Unit\CommandBus;

use App\Commands\MyCommand;
use function Tempest\command;
use Tempest\Commands\CommandBus;
use Tempest\Commands\CommandBusConfig;
use Tempest\Commands\CommandHandlerNotFound;
use Tests\Tempest\TestCase;
use Tests\Tempest\Unit\CommandBus\Fixtures\MyCommandBusMiddleware;

class CommandBusTest extends TestCase
{
    /** @test */
    public function command_handlers_are_auto_discovered()
    {
        $command = new MyCommand();

        command($command);

        $bus = $this->container->get(CommandBus::class);

        $this->assertEquals([$command], $bus->getHistory());
    }

    /** @test */
    public function command_bus_with_middleware()
    {
        MyCommandBusMiddleware::$hit = false;

        $config = $this->container->get(CommandBusConfig::class);

        $config->addMiddleware(new MyCommandBusMiddleware());

        command(new MyCommand());

        $this->assertTrue(MyCommandBusMiddleware::$hit);
    }

    /** @test */
    public function unknown_handler_throws_exception()
    {
        $this->expectException(CommandHandlerNotFound::class);

        command(new class () {});
    }
}
