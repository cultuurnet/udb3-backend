<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

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
