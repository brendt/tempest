<?php

declare(strict_types=1);

namespace Tempest\Database\Connections;

use SensitiveParameter;
use Tempest\Database\DatabaseDialect;
use Tempest\Database\Tables\NamingStrategy;
use Tempest\Database\Tables\PluralizedSnakeCaseStrategy;

final readonly class SQLiteConnection implements DatabaseConnection
{
    public function __construct(
        #[SensitiveParameter]
        public string $path = 'localhost',
        public NamingStrategy $namingStrategy = new PluralizedSnakeCaseStrategy(),
    ) {
    }

    public function getDsn(): string
    {
        return "sqlite:{$this->path}";
    }

    public function getUsername(): ?string
    {
        return null;
    }

    public function getPassword(): ?string
    {
        return null;
    }

    public function dialect(): DatabaseDialect
    {
        return DatabaseDialect::SQLITE;
    }

    public function tableNamingStrategy(): NamingStrategy
    {
        return $this->namingStrategy;
    }
}
