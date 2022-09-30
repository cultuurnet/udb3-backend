<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20201019121035 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $schema->dropTable('roles_search');
    }

    public function down(Schema $schema): void
    {
    }
}
