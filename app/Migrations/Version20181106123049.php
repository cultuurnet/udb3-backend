<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use CultuurNet\UDB3\Role\ReadModel\Search\Doctrine\SchemaConfigurator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

class Version20181106123049 extends AbstractMigration
{
    private const ROLES_SEARCH_V3 = 'roles_search_v3';


    public function up(Schema $schema): void
    {
        $table = $schema->createTable(self::ROLES_SEARCH_V3);

        $table->addColumn(
            SchemaConfigurator::UUID_COLUMN,
            Types::GUID,
            ['length' => 36]
        );

        $table->addColumn(
            SchemaConfigurator::NAME_COLUMN,
            Types::STRING
        )
            ->setLength(255);

        $table->addColumn(
            SchemaConfigurator::CONSTRAINT_COLUMN,
            Types::STRING
        )
            ->setNotnull(false);

        $table->setPrimaryKey([SchemaConfigurator::UUID_COLUMN]);
        $table->addUniqueIndex(
            [
                SchemaConfigurator::UUID_COLUMN,
                SchemaConfigurator::NAME_COLUMN,
            ]
        );
    }


    public function down(Schema $schema): void
    {
        $schema->dropTable(self::ROLES_SEARCH_V3);
    }
}
