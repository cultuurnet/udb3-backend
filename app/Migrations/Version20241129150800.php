<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241129150800 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->createTable('event_place_history');

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

        $table->addIndex(['old_place', 'date', 'event'], 'idx_old_place');
        $table->addIndex(['new_place', 'date', 'event'], 'idx_new_place');
        $table->addIndex(['event'], 'idx_event');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('event_place_history');
    }
}
