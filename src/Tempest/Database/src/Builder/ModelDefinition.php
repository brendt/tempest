<?php

declare(strict_types=1);

namespace Tempest\Database\Builder;

use Tempest\Database\BelongsTo;
use Tempest\Database\Builder\Relations\BelongsToRelation;
use Tempest\Database\Builder\Relations\HasManyRelation;
use Tempest\Database\Builder\Relations\HasOneRelation;
use Tempest\Database\Eager;
use Tempest\Database\HasMany;
use Tempest\Database\HasOne;
use Tempest\Reflection\ClassReflector;
use function Tempest\reflect;

final readonly class ModelDefinition
{
    public function __construct(
        /** @var class-string<\Tempest\Database\DatabaseModel> $modelClass */
        private string $modelClass,
    ) {
    }

    /** @return \Tempest\Database\Builder\Relations\Relation[] */
    public function getRelations(string $relationName): array
    {
        $relations = [];
        $class = reflect($this->modelClass);
        $relationNames = explode('.', $relationName);
        $alias = TableName::for($class)->tableName;

        foreach ($relationNames as $relationNamePart) {
            $property = $class->getProperty($relationNamePart);

            if ($property->hasAttribute(HasMany::class)) {
                /** @var HasMany $relationAttribute */
                $relationAttribute = $property->getAttribute(HasMany::class);
                $relations[] = HasManyRelation::fromAttribute($relationAttribute, $property, $alias);
                $class = HasManyRelation::getRelationModelClass($property, $relationAttribute)->getType()->asClass();
                $alias .= ".{$property->getName()}";
            } elseif ($property->getType()->isIterable()) {
                $relations[] = HasManyRelation::fromInference($property, $alias);
                $class = $property->getIterableType()->asClass();
                $alias .= ".{$property->getName()}[]";
            } elseif ($property->hasAttribute(HasOne::class)) {
                $relations[] = new HasOneRelation($property, $alias);
                $class = $property->getType()->asClass();
                $alias .= ".{$property->getName()}";
            } elseif ($property->hasAttribute(BelongsTo::class)) {
                /** @var BelongsTo $relationAttribute */
                $relationAttribute = $property->getAttribute(BelongsTo::class);
                $relations[] = BelongsToRelation::fromAttribute($relationAttribute, $property, $alias);
                $class = $property->getType()->asClass();
                $alias .= ".{$property->getName()}";
            } else {
                $relations[] = BelongsToRelation::fromInference($property, $alias);
                $class = $property->getType()->asClass();
                $alias .= ".{$property->getName()}";
            }
        }

        return $relations;
    }

    /** @return \Tempest\Database\Builder\Relations\Relation[] */
    public function getEagerRelations(): array
    {
        $relations = [];

        foreach ($this->buildEagerRelationNames(reflect($this->modelClass)) as $relationName) {
            foreach ($this->getRelations($relationName) as $relation) {
                $relations[$relation->getRelationName()] = $relation;
            }
        }

        return $relations;
    }

    private function buildEagerRelationNames(ClassReflector $class): array
    {
        $relations = [];

        foreach ($class->getPublicProperties() as $property) {
            if (! $property->hasAttribute(Eager::class)) {
                continue;
            }

            $relations[] = $property->getName();

            foreach ($this->buildEagerRelationNames($property->getType()->asClass()) as $childRelation) {
                $relations[] = "{$property->getName()}.{$childRelation}";
            }
        }

        return $relations;
    }

    public function getTableName(): TableName
    {
        return ($this->modelClass)::table();
    }

    public function getFieldName(string $fieldName): FieldName
    {
        return new FieldName(
            tableName: $this->getTableName(),
            fieldName: $fieldName,
        );
    }

    /** @return \Tempest\Database\Builder\FieldName[] */
    public function getFieldNames(): array
    {
        return FieldName::make(reflect($this->modelClass));
    }
}
