<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20200909121744 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->createTable('similar_events');
        $table->addColumn(
            'similarity',
            'decimal',
            [
                'notnull' => true,
            ]
        );
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
        $schema->dropTable('similar_events');
    }
}
