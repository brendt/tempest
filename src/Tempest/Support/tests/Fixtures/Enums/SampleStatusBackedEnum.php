<?php

namespace Tempest\Support\Tests\Fixtures\Enums;

use Tempest\Support\Enums\Enumerates;

enum SampleStatusBackedEnum: string {
    use Enumerates;
    
    case PUBLISH = 'publish';
    case DRAFT = 'draft';
    case TRASH = 'trash';
}