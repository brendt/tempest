<?php

declare(strict_types=1);

namespace Tempest\Database\Stubs;

use Tempest\Database\Migrations\Migration;
use Tempest\Database\QueryStatement;
use Tempest\Database\QueryStatements\CreateTableStatement;
use Tempest\Database\QueryStatements\DropTableStatement;

final class MigrationModelStub
{
    public function getName(): string {
        return 'dummy-date_dummy-name';
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
