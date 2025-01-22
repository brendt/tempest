<?php

declare(strict_types=1);

namespace Tests\Tempest\Fixtures\Discovery;

use Tempest\Database\DatabaseMigration;
use Tempest\Database\QueryStatement;
use Tempest\Discovery\DoNotDiscover;

#[DoNotDiscover]
final class HiddenDatabaseMigration implements DatabaseMigration
{
    private(set) string $name = 'hidden-migration';

    public function up(): ?QueryStatement
    {
        return null;
    }

    public function down(): ?QueryStatement
    {
        return null;
    }
}
