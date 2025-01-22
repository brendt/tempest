<?php

declare(strict_types=1);

namespace Tempest\Support\Tests\Fixtures\Enums;

use Tempest\Support\Enums\IsEnum;

enum SampleStatusBackedEnum: string
{
    use IsEnum;

    case PUBLISH = 'publish';
    case DRAFT = 'draft';
    case TRASH = 'trash';
}
