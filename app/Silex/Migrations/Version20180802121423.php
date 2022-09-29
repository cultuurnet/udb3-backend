<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180802121423 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $table = $schema->getTable('index_readmodel');

        $table->addColumn('country', Type::STRING)
            ->setLength(2);
    }


    public function postUp(Schema $schema)
    {
        $this->connection->executeQuery("UPDATE index_readmodel SET country = 'BE'");
    }


    public function down(Schema $schema)
    {
        $schema
            ->getTable('index_readmodel')
            ->dropColumn('country');
    }
}
