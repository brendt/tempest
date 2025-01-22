<?php

declare(strict_types=1);

namespace Tempest\Database\Builder\Relations;

use Tempest\Database\BelongsTo;
use Tempest\Database\Builder\FieldName;
use Tempest\Database\Builder\TableName;
use Tempest\Reflection\ClassReflector;
use Tempest\Reflection\PropertyReflector;

final readonly class BelongsToRelation implements Relation
{
    private function __construct(
        private ClassReflector $relationModelClass,
        private FieldName $localField,
        private FieldName $joinField,
    ) {
    }

    public static function fromInference(PropertyReflector $property, string $alias): self
    {
        $relationModelClass = $property->getType()->asClass();

        $localTable = TableName::for($property->getClass(), $alias);
        $localField = new FieldName($localTable, $property->getName() . '_id');

        $joinTable = TableName::for($property->getType()->asClass(), "{$alias}.{$property->getName()}");
        $joinField = new FieldName($joinTable, 'id');

        return new self($relationModelClass, $localField, $joinField);
    }

    public static function fromAttribute(BelongsTo $belongsTo, PropertyReflector $property, string $alias): self
    {
        $relationModelClass = $property->getType()->asClass();

        $localTable = TableName::for($property->getClass(), $alias);
        $localField = new FieldName($localTable, $belongsTo->localPropertyName);

        $joinTable = TableName::for($property->getType()->asClass(), "{$alias}.{$property->getName()}");
        $joinField = new FieldName($joinTable, $belongsTo->inversePropertyName);

        return new self($relationModelClass, $localField, $joinField);
    }

    public function getStatement(): string
    {
        return sprintf(
            'LEFT JOIN %s ON %s = %s',
            $this->joinField->tableName,
            $this->localField,
            $this->joinField,
        );
    }

    public function getRelationName(): string
    {
        return $this->joinField->tableName->as;
    }

    public function getFieldNames(): array
    {
        return FieldName::make($this->relationModelClass, $this->joinField->tableName);
    }
}
