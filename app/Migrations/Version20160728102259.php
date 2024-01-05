<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20160728102259 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->changeColumnName('relationType', 'offerType');
        $this->changeColumnName('relationId', 'offerId');
    }


    public function down(Schema $schema): void
    {
        $this->changeColumnName('offerType', 'relationType');
        $this->changeColumnName('offerId', 'relationId');
    }

    /**
     * @param string $oldName
     * @param string $newName
     */
    private function changeColumnName($oldName, $newName): void
    {
        $this->connection->exec(
            "ALTER TABLE labels_relations CHANGE $oldName $newName VARCHAR(255)"
        );
    }
}
