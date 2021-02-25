<?php

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160506142905 extends AbstractMigration
{
    public const PLACE_RELATIONS = 'place_relations';

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
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

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable(self::PLACE_RELATIONS);
    }
}
