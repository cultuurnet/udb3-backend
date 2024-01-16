<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

class Version20211028130632 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->createTable('event_recommendations');
        $table->addColumn('event_id', Types::GUID)->setLength(36)->setNotnull(true);
        $table->addColumn('recommended_event_id', Types::GUID)->setLength(36)->setNotnull(true);
        $table->addColumn('score', 'decimal', ['notnull' => true, 'scale' => 2]);
        $table->setPrimaryKey(['event_id']);
        $table->addIndex(['recommended_event_id']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('event_recommendations');
    }
}
