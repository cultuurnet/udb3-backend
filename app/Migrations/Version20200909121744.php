<?php

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

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

    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
