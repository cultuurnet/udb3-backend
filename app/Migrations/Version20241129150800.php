<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241129150800 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->createTable('event_location_history');

        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('event', 'guid', ['length' => 36]);
        $table->addColumn('old_place', 'guid', ['length' => 36,
            'notnull' => false,
        ]);
        $table->addColumn('new_place', 'guid', ['length' => 36]);
        $table->addColumn(
            'date',
            'datetime_immutable',
            ['notnull' => true]
        );

        $table->setPrimaryKey(['id']);

        // Index because I assume one of the most asked questions is going to be: "where are all the events of place X?"
        $table->addIndex(['old_place'], 'idx_old_place');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('event_location_history');
    }
}
