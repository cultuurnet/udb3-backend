<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

class Version20160930161518 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->createTable('organizer_unique_websites');

        $table->addColumn('uuid_col', Types::GUID)
            ->setLength(36)
            ->setNotnull(true);

        $table->addColumn('unique_col', Types::STRING)
            ->setLength(255)
            ->setNotnull(true);

        $table->setPrimaryKey(['uuid_col']);
        $table->addUniqueIndex(['uuid_col']);
        $table->addUniqueIndex(['unique_col']);
    }


    public function down(Schema $schema): void
    {
        $schema->dropTable('organizer_unique_websites');
    }
}
