<?php

declare(strict_types=1);

namespace Tempest\Database\Connections;

use SensitiveParameter;
use Tempest\Database\DatabaseDialect;
use Tempest\Database\Tables\NamingStrategy;
use Tempest\Database\Tables\PluralizedSnakeCaseStrategy;

final readonly class MySqlConnection implements DatabaseConnection
{
    public function __construct(
        #[SensitiveParameter]
        public string $host = 'localhost',
        #[SensitiveParameter]
        public string $port = '3306',
        #[SensitiveParameter]
        public string $username = 'root',
        #[SensitiveParameter]
        public string $password = '',
        #[SensitiveParameter]
        public string $database = 'app',
        public NamingStrategy $namingStrategy = new PluralizedSnakeCaseStrategy(),
    ) {
    }

    public function getDsn(): string
    {
        return "mysql:host={$this->host}:{$this->port};dbname={$this->database}";
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function dialect(): DatabaseDialect
    {
        return DatabaseDialect::MYSQL;
    }

    public function tableNamingStrategy(): NamingStrategy
    {
        return $this->namingStrategy;
    }
}
