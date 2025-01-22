<?php

declare(strict_types=1);

namespace Tempest\Database\Tests\Relations\Fixtures;

use Tempest\Database\BelongsTo;
use Tempest\Database\Builder\TableName;
use Tempest\Database\DatabaseModel;
use Tempest\Database\IsDatabaseModel;

final class BelongsToParentModel implements DatabaseModel
{
    use IsDatabaseModel;

    public static function table(): TableName
    {
        return new TableName('belongs_to_parent_model');
    }

    public BelongsToRelatedModel $relatedModel;

    #[BelongsTo('other_id')]
    public BelongsToRelatedModel $otherRelatedModel;

    #[BelongsTo('other_id', 'other_id')]
    public BelongsToRelatedModel $stillOtherRelatedModel;
}
