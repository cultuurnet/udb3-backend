<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\Migrations\AbstractMigration;

class Version20210325170924 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->createTable('offer_metadata');
        $table->addColumn('id', Type::GUID)->setLength(36)->setNotnull(true);
        $table->addColumn('created_by_api_consumer', Type::STRING)->setLength(255)->setNotnull(true);
        $table->setPrimaryKey(['id']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('offer_metadata');
    }
}
