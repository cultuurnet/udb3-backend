<?php

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20201015123012 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $schema->dropTable('saved_searches_sapi2');
    }

    public function down(Schema $schema): void
    {
    }
}
