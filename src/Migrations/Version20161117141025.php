<?php

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161117141025 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->changeColumnName('offerType', 'relationType');
        $this->changeColumnName('offerId', 'relationId');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->changeColumnName('relationType', 'offerType');
        $this->changeColumnName('relationId', 'offerId');
    }

    /**
     * @param string $oldName
     * @param string $newName
     */
    private function changeColumnName($oldName, $newName)
    {
        $this->connection->exec(
            "ALTER TABLE labels_relations CHANGE $oldName $newName VARCHAR(255)"
        );
    }
}
