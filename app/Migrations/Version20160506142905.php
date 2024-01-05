<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20160506142905 extends AbstractMigration
{
    public const PLACE_RELATIONS = 'place_relations';


    public function up(Schema $schema): void
    {
        $table = $schema->createTable(self::PLACE_RELATIONS);

        $table->addColumn(
            'place',
            'string',
            ['length' => 36, 'notnull' => false]
        );
        $table->addColumn(
            'organizer',
            'string',
            ['length' => 36, 'notnull' => false]
        );

        $table->setPrimaryKey(['place']);
    }


    public function down(Schema $schema): void
    {
        $schema->dropTable(self::PLACE_RELATIONS);
    }
}
