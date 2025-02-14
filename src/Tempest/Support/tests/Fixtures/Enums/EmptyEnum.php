<?php

declare(strict_types=1);

namespace Tempest\Support\Tests\Fixtures\Enums;

use Tempest\Support\Enums\HelperMethods;

enum EmptyEnum: string
{
    use HelperMethods;
}
