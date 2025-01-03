<?php

namespace Tempest\Support\Tests\Fixtures\Enums;

use Tempest\Support\Enums\InvokableCases;

enum SampleStatusBackedEnum: string {
    use InvokableCases;
    
    case PUBLISH = 'publish';
    case DRAFT = 'draft';
    case TRASH = 'trash';
}