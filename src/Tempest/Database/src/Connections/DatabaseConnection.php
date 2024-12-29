<?php

declare(strict_types=1);

namespace Tempest\Database\Connections;

use Tempest\Database\DatabaseDialect;
use Tempest\Database\Tables\NamingStrategy;

interface DatabaseConnection
{
    public function getDsn(): string;

    public function getUsername(): ?string;

    public function getPassword(): ?string;

    public function dialect(): DatabaseDialect;

    public function tableNamingStrategy(): NamingStrategy;
}
