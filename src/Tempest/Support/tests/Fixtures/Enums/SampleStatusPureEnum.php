<?php

namespace Tempest\Support\Tests\Fixtures\Enums;

use Tempest\Support\Enums\InvokableCases;

enum SampleStatusPureEnum {
    use InvokableCases;
    
    case PUBLISH;
    case DRAFT;
    case TRASH;
}