<?php

namespace Tempest\Support\Tests\Fixtures\Enums;

use Tempest\Support\Enums\HelperMethods;
use Tempest\Support\Enums\InvokableCases;

enum SampleStatusPureEnum {
    use InvokableCases;
    use HelperMethods;
    
    case PUBLISH;
    case DRAFT;
    case TRASH;
}