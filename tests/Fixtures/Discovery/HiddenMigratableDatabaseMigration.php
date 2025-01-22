<?php

declare(strict_types=1);

namespace Tests\Tempest\Fixtures\Discovery;

use Tempest\Database\DatabaseMigration;
use Tempest\Database\MigrationDiscovery;
use Tempest\Database\QueryStatement;
use Tempest\Discovery\DoNotDiscover;

#[DoNotDiscover(except: [MigrationDiscovery::class])]
final class HiddenMigratableDatabaseMigration implements DatabaseMigration
{
    private(set) string $name = 'hidden-migratable-migration';

    public function up(): ?QueryStatement
    {
        return null;
    }

    public function down(): ?QueryStatement
    {
        return null;
    }
}
