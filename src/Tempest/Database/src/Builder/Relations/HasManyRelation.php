<?php

declare(strict_types=1);

namespace Tempest\Database\Builder\Relations;

use Tempest\Database\Builder\FieldName;
use Tempest\Database\Builder\TableName;
use Tempest\Database\Exceptions\InvalidRelation;
use Tempest\Database\HasMany;
use Tempest\Reflection\ClassReflector;
use Tempest\Reflection\PropertyReflector;

final readonly class HasManyRelation implements Relation
{
    private function __construct(
        private ClassReflector $relationModelClass,
        private FieldName $localField,
        private FieldName $joinField,
    ) {
    }

    public static function fromInference(PropertyReflector $property, string $alias): self
    {
        $relationModelClass = self::getRelationModelClass($property);

        $inverseProperty = null;

        foreach ($relationModelClass->getPublicProperties() as $potentialInverseProperty) {
            if ($potentialInverseProperty->getType()->equals($property->getClass()->getType())) {
                $inverseProperty = $potentialInverseProperty;

                break;
            }
        }

        if ($inverseProperty === null) {
            throw InvalidRelation::inversePropertyNotFound(
                $property->getClass()->getName(),
                $property->getName(),
                $relationModelClass->getName(),
            );
        }

        $localTable = TableName::for($property->getClass(), $alias);
        $localField = new FieldName($localTable, 'id');

        $joinTable = TableName::for($relationModelClass, "{$alias}.{$property->getName()}[]");
        $joinField = new FieldName($joinTable, $inverseProperty->getName() . '_id');

        return new self($relationModelClass, $localField, $joinField);
    }

    public static function getRelationModelClass(
        PropertyReflector $property,
        HasMany|null $relation = null,
    ): ClassReflector {
        if ($relation !== null && $relation->inverseClassName !== null) {
            return new ClassReflector($relation->inverseClassName);
        }

        return $property->getIterableType()->asClass();
    }

    public static function fromAttribute(HasMany $relation, PropertyReflector $property, string $alias): self
    {
        $relationModelClass = self::getRelationModelClass($property, $relation);

        $localTable = TableName::for($property->getClass(), $alias);
        $localField = new FieldName($localTable, $relation->localPropertyName);

        $joinTable = TableName::for($relationModelClass, "{$alias}.{$property->getName()}[]");
        $joinField = new FieldName($joinTable, $relation->inversePropertyName);

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
