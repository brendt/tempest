<?php

namespace Tempest\Support\Tests\Fixtures\Enums;

use Tempest\Support\Enums\Enumerates;

enum SampleIntegerBackedEnum: int {
    use Enumerates;
    
    case SUCCESS = 200;
    case CREATED = 201;
    case NOT_FOUND = 404;
    case SERVER_ERROR = 500;
}