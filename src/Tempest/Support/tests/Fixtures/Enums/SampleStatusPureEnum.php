<?php

namespace Tempest\Support\Tests\Fixtures\Enums;

use Tempest\Support\Enums\Enumerates;

enum SampleStatusPureEnum {
    use Enumerates;
    
    case PUBLISH;
    case DRAFT;
    case TRASH;
}