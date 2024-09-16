<?php

declare(strict_types=1);

namespace Tests\Tempest\Fixtures\Modules\Books\Requests;

use Tempest\Router\IsRequest;
use Tempest\Router\Request;

final class CreateBookRequest implements Request
{
    use IsRequest;

    public string $title;
}
