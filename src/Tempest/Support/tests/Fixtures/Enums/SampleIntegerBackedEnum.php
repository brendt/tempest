<?php

declare(strict_types=1);

namespace Tempest\Support\Tests\Fixtures\Enums;

use Tempest\Support\Enums\IsEnum;

enum SampleIntegerBackedEnum: int
{
    use IsEnum;

    case SUCCESS = 200;
    case CREATED = 201;
    case NOT_FOUND = 404;
    case SERVER_ERROR = 500;
}
