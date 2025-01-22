<?php

declare(strict_types=1);

namespace Tempest\Database\Migrations;

use Tempest\Database\DatabaseMigration;
use Tempest\Database\Migrations\Migration as Model;
use Tempest\Database\QueryStatement;
use Tempest\Database\QueryStatements\CreateTableStatement;
use Tempest\Database\QueryStatements\DropTableStatement;

final class CreateMigrationsTable implements DatabaseMigration
{
    private(set) string $name = '0000-00-00_create_migrations_table';

    public function up(): QueryStatement
    {
        return new CreateTableStatement(Model::table()->tableName)
            ->primary()
            ->text('name');
    }

    public function down(): QueryStatement
    {
        return new DropTableStatement(Model::table()->tableName);
    }
}
