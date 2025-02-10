<?php

declare(strict_types=1);

namespace Tempest\CommandBus;

use Exception;

final class CommandHandlerNotFound extends Exception
{
    public function __construct(object $command)
    {
        $commandName = $command::class;

        parent::__construct("No handler found for [{$commandName}].");
    }
}
