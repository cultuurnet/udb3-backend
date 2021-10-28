<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\Migrations\AbstractMigration;

class Version20211028130632 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->createTable('event_recommendations');
        $table->addColumn('event_id', Type::GUID)->setLength(36)->setNotnull(true);
        $table->addColumn('recommended_event_id', Type::GUID)->setLength(36)->setNotnull(true);
        $table->addColumn('score', 'decimal', ['notnull' => true, 'scale' => 2]);
        $table->setPrimaryKey(['event_id']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('event_recommendations');
    }
}
