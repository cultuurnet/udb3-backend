<?php

namespace CultuurNet\UDB3\Silex\Migrations;

use CultuurNet\UDB3\Role\ReadModel\Search\Doctrine\SchemaConfigurator;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

/**
 * Add roles_search_v3 table.
 */
class Version20181106123049 extends AbstractMigration
{
    private const ROLES_SEARCH_V3 = 'roles_search_v3';

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $table = $schema->createTable(self::ROLES_SEARCH_V3);

        $table->addColumn(
            SchemaConfigurator::UUID_COLUMN,
            Type::GUID,
            ['length' => 36]
        );

        $table->addColumn(
            SchemaConfigurator::NAME_COLUMN,
            Type::STRING
        )
            ->setLength(255);

        $table->addColumn(
            SchemaConfigurator::CONSTRAINT_COLUMN,
            Type::STRING
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

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable(self::ROLES_SEARCH_V3);
    }
}
