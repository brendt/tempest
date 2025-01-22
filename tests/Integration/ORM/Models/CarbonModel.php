<?php

declare(strict_types=1);

namespace Tests\Tempest\Integration\ORM\Models;

use Carbon\Carbon;
use Tempest\Database\DatabaseModel;
use Tempest\Database\IsDatabaseModel;

final class CarbonModel implements DatabaseModel
{
    use IsDatabaseModel;

    public function __construct(
        public Carbon $createdAt,
    ) {
    }
}
