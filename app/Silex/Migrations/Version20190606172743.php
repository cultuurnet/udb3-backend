<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190606172743 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $schema->dropTable('index_readmodel');
    }


    public function down(Schema $schema)
    {
    }
}
