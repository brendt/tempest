<?php

declare(strict_types=1);

namespace Tempest\Database\Stubs;

use Tempest\Database\DatabaseMigration;
use Tempest\Database\QueryStatement;
use Tempest\Database\QueryStatements\CreateTableStatement;
use Tempest\Database\QueryStatements\DropTableStatement;

final class MigrationModelStub implements DatabaseMigration
{
    public string $name {
        get => 'dummy-date_dummy-table-name';
    }

    public function up(): ?QueryStatement {
        return CreateTableStatement::forModel('DummyModel')
            ->primary()
            ->text('name')
            ->datetime('created_at')
            ->datetime('updated_at');
    }

    public function down(): ?QueryStatement {
        return DropTableStatement::forModel('DummyModel');
    }
}
