<?php

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20200804090405 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table =  $schema->getTable('productions');

        // Increase the max-length of the name column from 32 to 255.
        // "Lord of the Rings: The Fellowship of the Ring" is already 45 characters for example.
        $table->changeColumn('name', [
            'length' => 255,
            'notnull' => true,
        ]);
    }

    public function down(Schema $schema): void
    {
        $table =  $schema->getTable('productions');

        $table->changeColumn('name', [
            'length' => 32,
            'notnull' => true,
        ]);
    }
}
