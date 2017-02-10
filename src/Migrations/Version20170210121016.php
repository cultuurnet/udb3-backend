<?php

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170210121016 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $table =$this->getTable($schema);
        $table->dropColumn('title');
        $table->dropColumn('zip');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $table =$this->getTable($schema);
        $table->addColumn('title', Type::TEXT);
        $table->addColumn('zip', Type::TEXT);
    }

    /**
     * @param Schema $schema
     * @return \Doctrine\DBAL\Schema\Table
     */
    private function getTable(Schema $schema)
    {
        return $schema->getTable('index_readmodel');
    }
}
