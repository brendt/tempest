<?php

declare(strict_types=1);

namespace Tempest\Database\QueryStatements;

use Tempest\Database\DatabaseDialect;
use Tempest\Database\Id;
use Tempest\Database\Query;

trait CanExecuteStatement
{
    public function execute(DatabaseDialect $dialect): Id
    {
        return new Query($this->compile($dialect))->execute();
    }
}
