<?php

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20201015095142 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $schema->dropTable('event_variation_search_index');
    }

    public function down(Schema $schema)
    {
    }
}
