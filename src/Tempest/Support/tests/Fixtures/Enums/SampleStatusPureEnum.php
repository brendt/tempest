<?php

declare(strict_types=1);

namespace Tempest\Support\Tests\Fixtures\Enums;

use Tempest\Support\Enums\IsEnum;

enum SampleStatusPureEnum
{
    use IsEnum;

    case PUBLISH;
    case DRAFT;
    case TRASH;
}
