<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use CultuurNet\UDB3\Role\ReadModel\Search\Doctrine\ColumnNames;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

class Version20160830161312 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->getTable('roles_search');

        $table->addColumn(ColumnNames::CONSTRAINT_COLUMN, Types::STRING)
            ->setNotnull(false);
    }


    public function down(Schema $schema): void
    {
        $table = $schema->getTable('roles_search');

        $table->dropColumn(ColumnNames::CONSTRAINT_COLUMN);
    }
}
