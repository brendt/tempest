<?php

declare(strict_types=1);

namespace Tests\Tempest\Integration\Exceptions;

use App\Console\FailCommand;
use Tempest\Exceptions\ConsoleExceptionHandler;
use Tempest\Testing\IntegrationTest;

/**
 * @internal
 * @small
 */
class ConsoleExceptionHandlerTest extends IntegrationTest
{
    public function test_exception()
    {
        $this->appConfig->enableExceptionHandling = true;
        $this->appConfig->exceptionHandlers = [
            $this->container->get(ConsoleExceptionHandler::class),
        ];

        $this->console
            ->call('fail output')
            ->assertContains(FailCommand::class)
            ->assertContains('__invoke');
    }
}
