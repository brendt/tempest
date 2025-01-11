<?php

declare(strict_types=1);

namespace Tempest\Database\Enums;

/**
 * Represents the type of migration.
 * Used to differentiate between raw and class migrations.
 */
enum MigrationType: string
{
    case RAW = 'raw'; // A raw migration file ( .sql )
    case MODEL = 'model'; // A migration class file for a model
    case OBJECT = 'class'; // A classic migration class file
}
