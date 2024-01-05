<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\Migrations\AbstractMigration;

class Version20180802121423 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->getTable('index_readmodel');

        $table->addColumn('country', Type::STRING)
            ->setLength(2);
    }


    public function postUp(Schema $schema): void
    {
        $this->connection->executeQuery("UPDATE index_readmodel SET country = 'BE'");
    }


    public function down(Schema $schema): void
    {
        $schema
            ->getTable('index_readmodel')
            ->dropColumn('country');
    }
}
