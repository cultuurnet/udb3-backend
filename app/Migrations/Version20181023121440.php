<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use CultuurNet\UDB3\SavedSearches\Doctrine\ColumnNames;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

class Version20181023121440 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->createTable('saved_searches_sapi2');

        $table->addColumn(ColumnNames::ID, Types::GUID)
            ->setLength(36)
            ->setNotnull(true);

        $table->addColumn(ColumnNames::USER, Types::GUID)
            ->setLength(36)
            ->setNotnull(true);

        $table->addColumn(ColumnNames::NAME, Types::STRING)
            ->setLength(255)
            ->setNotnull(true);

        $table->addColumn(ColumnNames::QUERY, Types::TEXT)
            ->setNotnull(true);

        $table->addIndex([ColumnNames::ID]);
        $table->addIndex([ColumnNames::USER]);
    }


    public function down(Schema $schema): void
    {
        $schema->dropTable('saved_searches_sapi2');
    }
}
