<?php

declare(strict_types=1);

namespace Tempest\Database;

use Tempest\Discovery\DiscoversPath;
use Tempest\Discovery\Discovery;
use Tempest\Discovery\DiscoveryLocation;
use Tempest\Discovery\IsDiscovery;
use Tempest\Reflection\ClassReflector;

final class MigrationDiscovery implements Discovery, DiscoversPath
{
    use IsDiscovery;

    public function __construct(private readonly DatabaseConfig $databaseConfig)
    {
    }

    public function discover(DiscoveryLocation $location, ClassReflector $class): void
    {
        if (! $class->implements(DatabaseMigration::class)) {
            return;
        }

        if ($class->is(GenericDatabaseMigration::class)) {
            return;
        }

        $this->discoveryItems->add($location, $class->getName());
    }

    public function discoverPath(DiscoveryLocation $location, string $path): void
    {
        if (! str_ends_with($path, '.sql')) {
            return;
        }

        $fileName = pathinfo($path, PATHINFO_FILENAME);

        $contents = explode(';', file_get_contents($path));

        foreach ($contents as $i => $content) {
            if (! $content) {
                continue;
            }

            $migration = new GenericDatabaseMigration(
                fileName: "{$fileName}_{$i}",
                content: $content,
            );

            $this->discoveryItems->add($location, $migration);
        }
    }

    public function apply(): void
    {
        foreach ($this->discoveryItems as $migration) {
            $this->databaseConfig->addMigration($migration);
        }
    }
}
