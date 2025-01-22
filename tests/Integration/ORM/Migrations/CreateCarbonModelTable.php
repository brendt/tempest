<?php

declare(strict_types=1);

namespace Tests\Tempest\Integration\ORM\Migrations;

use Tempest\Database\DatabaseMigration;
use Tempest\Database\QueryStatement;
use Tempest\Database\QueryStatements\CreateTableStatement;
use Tempest\Database\QueryStatements\DropTableStatement;
use Tests\Tempest\Integration\ORM\Models\CarbonModel;

final class CreateCarbonModelTable implements DatabaseMigration
{
    public string $name = '2024-12-17_create_users_table';

    public function up(): QueryStatement
    {
        return CreateTableStatement::forModel(CarbonModel::class)
            ->primary()
            ->raw('`createdAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
    }

    public function down(): QueryStatement
    {
        return DropTableStatement::forModel(CarbonModel::class);
    }
}
