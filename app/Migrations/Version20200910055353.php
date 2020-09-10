<?php

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20200910055353 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->createTable('similar_events_skipped');
        $table->addColumn(
            'event1',
            'guid',
            [
                'length' => 36,
                'notnull' => true,
            ]
        );
        $table->addColumn(
            'event2',
            'guid',
            [
                'length' => 36,
                'notnull' => true,
            ]
        );
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('similar_events_skipped');
    }
}
