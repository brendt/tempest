<?php

declare(strict_types=1);

namespace Tempest\Database\Tests\Relations\Fixtures;

use Tempest\Database\Builder\TableName;
use Tempest\Database\DatabaseModel;
use Tempest\Database\HasMany;
use Tempest\Database\IsDatabaseModel;

final class BelongsToRelatedModel implements DatabaseModel
{
    use IsDatabaseModel;

    /** @var \Tempest\Database\Tests\Relations\Fixtures\BelongsToParentModel[] */
    public array $inferred = [];

    #[HasMany('other_id')]
    /** @var \Tempest\Database\Tests\Relations\Fixtures\BelongsToParentModel[] */
    public array $attribute = [];

    #[HasMany('other_id', BelongsToParentModel::class, 'other_id')]
    public array $full = [];

    /** @var \Tempest\Database\Tests\Relations\Fixtures\HasOneParentModel[] */
    public array $invalid = [];

    public static function table(): TableName
    {
        return new TableName('belongs_to_related');
    }
}
