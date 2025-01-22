<?php

declare(strict_types=1);

namespace Tempest\Database\Builder;

use Stringable;
use Tempest\Database\DatabaseModel;
use Tempest\Mapper\Casters\CasterFactory;
use Tempest\Reflection\ClassReflector;

final class FieldName implements Stringable
{
    public function __construct(
        public readonly TableName $tableName,
        public readonly string $fieldName,
        public ?string $as = null,
    ) {
    }

    /** @return \Tempest\Database\Builder\FieldName[] */
    public static function make(ClassReflector $class, ?TableName $tableName = null): array
    {
        $casterFactory = new CasterFactory();
        $fieldNames = [];
        $tableName ??= $class->callStatic('table');

        foreach ($class->getPublicProperties() as $property) {
            // Don't include the field if it's a relation
            if ($property->getType()->matches(DatabaseModel::class)) {
                continue;
            }

            $caster = $casterFactory->forProperty($property);

            if ($caster !== null) {
                $fieldNames[] = new FieldName($tableName, $property->getName());

                continue;
            }

            $type = $property->getType();

            if ($type->isIterable()) {
                continue;
            }

            if (! $type->isBuiltIn()) {
                continue;
            }

            $fieldNames[] = new FieldName($tableName, $property->getName());
        }

        return $fieldNames;
    }

    public function as(string $as): self
    {
        $this->as = $as;

        return $this;
    }

    public function withAlias(): self
    {
        $tableName = $this->tableName->as ?? $this->tableName->tableName;

        return $this->as($tableName . '.' . $this->fieldName);
    }

    public function __toString(): string
    {
        $tableName = $this->tableName->as ?? $this->tableName->tableName;

        $string = "`{$tableName}`.`{$this->fieldName}`";

        if ($this->as) {
            $string .= " AS `{$this->as}`";
        }

        return $string;
    }
}
