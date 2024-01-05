<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

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
