<?php

declare(strict_types=1);

use Tempest\Core\DiscoveryLocation;
use Tempest\Http\HttpApplication;

require_once __DIR__ . '/../vendor/autoload.php';

HttpApplication::boot(__DIR__ . '/../', discoveryLocations: [
    new DiscoveryLocation('Tests\\Tempest\\Fixtures\\', __DIR__ . '/../tests/Fixtures/'),
])->run();

exit;
