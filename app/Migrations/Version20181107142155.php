<?php

namespace CultuurNet\UDB3\Silex\Migrations;

use CultuurNet\UDB3\SavedSearches\Doctrine\SchemaConfigurator;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

/**
 * Add the table for SAPI3 saved searches.
 */
class Version20181107142155 extends AbstractMigration
{
    private const SAVED_SEARCHES_SAPI3 = 'saved_searches_sapi3';

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $table = $schema->createTable(self::SAVED_SEARCHES_SAPI3);

        $table->addColumn(SchemaConfigurator::ID, Type::GUID)
            ->setLength(36)
            ->setNotnull(true);

        $table->addColumn(SchemaConfigurator::USER, Type::GUID)
            ->setLength(36)
            ->setNotnull(true);

        $table->addColumn(SchemaConfigurator::NAME, Type::STRING)
            ->setLength(255)
            ->setNotnull(true);

        $table->addColumn(SchemaConfigurator::QUERY, Type::TEXT)
            ->setNotnull(true);

        $table->addIndex([SchemaConfigurator::ID]);
        $table->addIndex([SchemaConfigurator::USER]);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable(self::SAVED_SEARCHES_SAPI3);
    }
}
