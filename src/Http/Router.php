<?php

declare(strict_types=1);

namespace Tempest\Http;

use Psr\Http\Message\ServerRequestInterface as PsrRequest;

interface Router
{
    public function dispatch(PsrRequest $request): Response;

    public function toUri(array|string $action, ...$params): string;
}
