<?php

declare(strict_types=1);

namespace Tempest\Database\QueryStatements;

use Tempest\Database\DatabaseDialect;
use Tempest\Database\Query;
use Tempest\Database\QueryStatement;
use Tempest\Database\UnsupportedDialect;

final readonly class ShowTablesStatement implements QueryStatement
{
    public function __construct()
    {
    }

    public function fetch(DatabaseDialect $dialect): array
    {
        return new Query($this->compile($dialect))->fetch();
    }

    public function compile(DatabaseDialect $dialect): string
    {
        return match ($dialect) {
            DatabaseDialect::MYSQL => "SHOW FULL TABLES WHERE table_type = 'BASE TABLE'",
            DatabaseDialect::SQLITE => "select type, name from sqlite_master where type = 'table' and name not like 'sqlite_%'",
            default => throw new UnsupportedDialect(),
        };
    }
}
