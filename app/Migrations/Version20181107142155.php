<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use CultuurNet\UDB3\SavedSearches\Doctrine\SchemaConfigurator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

class Version20181107142155 extends AbstractMigration
{
    private const SAVED_SEARCHES_SAPI3 = 'saved_searches_sapi3';


    public function up(Schema $schema): void
    {
        $table = $schema->createTable(self::SAVED_SEARCHES_SAPI3);

        $table->addColumn(SchemaConfigurator::ID, Types::GUID)
            ->setLength(36)
            ->setNotnull(true);

        $table->addColumn(SchemaConfigurator::USER, Types::GUID)
            ->setLength(36)
            ->setNotnull(true);

        $table->addColumn(SchemaConfigurator::NAME, Types::STRING)
            ->setLength(255)
            ->setNotnull(true);

        $table->addColumn(SchemaConfigurator::QUERY, Types::TEXT)
            ->setNotnull(true);

        $table->addIndex([SchemaConfigurator::ID]);
        $table->addIndex([SchemaConfigurator::USER]);
    }


    public function down(Schema $schema): void
    {
        $schema->dropTable(self::SAVED_SEARCHES_SAPI3);
    }
}
