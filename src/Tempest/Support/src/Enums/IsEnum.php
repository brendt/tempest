<?php

declare(strict_types=1);

namespace Tempest\Support\Enums;

use Tempest\Support\Enums\InvokableCases;
use Tempest\Support\Enums\HelperMethods;
use Tempest\Support\Enums\Comparable;

/**
 * Use this trait to supercharge your enums with all enums features at once
 * You can still use the traits individually if you want to have more fine-grained control
 */
trait IsEnum
{
    use InvokableCases;
    use HelperMethods;
    use Comparable;
    use Accessible;
}
