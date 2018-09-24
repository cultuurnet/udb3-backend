<?php

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180802121423 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $table = $schema->getTable('index_readmodel');

        $table->addColumn('country', Type::STRING)
            ->setLength(2);
    }

    /**
     * @param Schema $schema
     */
    public function postUp(Schema $schema)
    {
        $this->connection->executeQuery("UPDATE index_readmodel SET country = 'BE'");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema
            ->getTable('index_readmodel')
            ->dropColumn('country');
    }
}
