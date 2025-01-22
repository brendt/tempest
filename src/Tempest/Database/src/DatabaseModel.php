<?php

declare(strict_types=1);

namespace Tempest\Database;

use Tempest\Database\Builder\ModelQueryBuilder;
use Tempest\Database\Builder\TableName;
use Tempest\Database\Casters\RelationCaster;
use Tempest\Mapper\CastWith;

#[CastWith(RelationCaster::class)]
interface DatabaseModel
{
    public static function table(): TableName;

    /** @return \Tempest\Database\Builder\ModelQueryBuilder<self> */
    public static function query(): ModelQueryBuilder;

    /** @return self[] */
    public static function all(): array;

    public static function new(mixed ...$params): self;

    public static function create(mixed ...$params): self;

    public static function updateOrCreate(array $find, array $update): self;

    public static function find(Id $id, array $relations = []): ?self;

    public function save(): self;

    public function getId(): Id;

    public function setId(Id $id): self;

    public function update(mixed ...$params): self;

    public function load(string ...$relations): self;
}
