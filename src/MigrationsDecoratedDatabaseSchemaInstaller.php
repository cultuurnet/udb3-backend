<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Silex;

use Doctrine\DBAL\Migrations\Configuration\Configuration;

class MigrationsDecoratedDatabaseSchemaInstaller implements DatabaseSchemaInstallerInterface
{
    /**
     * @var DatabaseSchemaInstaller
     */
    private $databaseSchemaInstaller;

    /**
     * @var Configuration
     */
    private $migrations;

    /**
     * @param DatabaseSchemaInstaller $databaseSchemaInstaller
     */
    public function __construct(
        DatabaseSchemaInstaller $databaseSchemaInstaller,
        Configuration $migrations
    ) {
        $this->databaseSchemaInstaller = $databaseSchemaInstaller;
        $this->migrations = $migrations;
    }

    public function installSchema()
    {
        $this->databaseSchemaInstaller->installSchema();

        $this->markVersionsMigrated();
    }

    private function markVersionsMigrated()
    {
        foreach ($this->migrations->getAvailableVersions() as $versionIdentifier) {
            $version = $this->migrations->getVersion($versionIdentifier);

            $version->markMigrated();
        }
    }
}
