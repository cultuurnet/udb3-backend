<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use CultuurNet\UDB3\Role\ReadModel\Search\Doctrine\ColumnNames;
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
            ColumnNames::UUID_COLUMN,
            Types::GUID,
            ['length' => 36]
        );

        $table->addColumn(
            ColumnNames::NAME_COLUMN,
            Types::STRING
        )
            ->setLength(255);

        $table->addColumn(
            ColumnNames::CONSTRAINT_COLUMN,
            Types::STRING
        )
            ->setNotnull(false);

        $table->setPrimaryKey([ColumnNames::UUID_COLUMN]);
        $table->addUniqueIndex(
            [
                ColumnNames::UUID_COLUMN,
                ColumnNames::NAME_COLUMN,
            ]
        );
    }


    public function down(Schema $schema): void
    {
        $schema->dropTable(self::ROLES_SEARCH_V3);
    }
}
