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

    public function up(): QueryStatement {
        return CreateTableStatement::forModel('DummyModel') // @phpstan-ignore-line argument.type (Because this is stub file and this param will be replaced by actual model name)
            ->primary()
            ->text('name')
            ->datetime('created_at')
            ->datetime('updated_at');
    }

    public function down(): QueryStatement {
        return DropTableStatement::forModel('DummyModel'); // @phpstan-ignore-line argument.type (Because this is stub file and this param will be replaced by actual model name)
    }
}
