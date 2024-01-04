<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20190606172743 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $schema->dropTable('index_readmodel');
    }


    public function down(Schema $schema): void
    {
    }
}
