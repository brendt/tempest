<?php

declare(strict_types=1);

namespace Tempest\Database\Tests\Relations;

use PHPUnit\Framework\TestCase;
use Tempest\Database\Builder\ModelDefinition;
use Tempest\Database\Exceptions\InvalidRelation;
use Tempest\Database\Tests\Relations\Fixtures\BelongsToRelatedModel;

/**
 * @internal
 */
final class HasManyRelationTest extends TestCase
{
    public function test_cannot_find_inverse(): void
    {
        $definition = new ModelDefinition(BelongsToRelatedModel::class);

        $this->expectException(InvalidRelation::class);
        $definition->getRelations('invalid');
    }

    public function test_inferred_has_many_relation(): void
    {
        $definition = new ModelDefinition(BelongsToRelatedModel::class);
        $inferredRelation = $definition->getRelations('inferred');

        $this->assertCount(1, $inferredRelation);
        $this->assertSame('belongs_to_related.inferred[]', $inferredRelation[0]->getRelationName());
        $this->assertEquals(
            'LEFT JOIN `belongs_to_parent_model` AS `belongs_to_related.inferred[]`' .
            ' ON `belongs_to_related`.`id` = `belongs_to_related.inferred[]`.`relatedModel_id`',
            $inferredRelation[0]->getStatement(),
        );
    }

    public function test_attribute_with_defaults_has_many_relation(): void
    {
        $definition = new ModelDefinition(BelongsToRelatedModel::class);
        $relation = $definition->getRelations('attribute');

        $this->assertCount(1, $relation);
        $this->assertSame('belongs_to_related.attribute[]', $relation[0]->getRelationName());
        $this->assertEquals(
            'LEFT JOIN `belongs_to_parent_model` AS `belongs_to_related.attribute[]`' .
            ' ON `belongs_to_related`.`id` = `belongs_to_related.attribute[]`.`other_id`',
            $relation[0]->getStatement(),
        );
    }

    public function test_fully_filled_attribute_has_many_relation(): void
    {
        $definition = new ModelDefinition(BelongsToRelatedModel::class);
        $relation = $definition->getRelations('full');

        $this->assertCount(1, $relation);
        $this->assertSame('belongs_to_related.full[]', $relation[0]->getRelationName());
        $this->assertEquals(
            'LEFT JOIN `belongs_to_parent_model` AS `belongs_to_related.full[]`' .
            ' ON `belongs_to_related`.`other_id` = `belongs_to_related.full[]`.`other_id`',
            $relation[0]->getStatement(),
        );
    }
}
