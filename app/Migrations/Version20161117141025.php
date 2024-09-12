<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20161117141025 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->changeColumnName('uuid_col', 'labelName');
        $this->changeColumnName('offerType', 'relationType');
        $this->changeColumnName('offerId', 'relationId');
    }


    public function down(Schema $schema): void
    {
        // Converting back down would loose data if the size was changed.
        $this->changeColumnName('labelName', 'uuid_col');
        $this->changeColumnName('relationType', 'offerType');
        $this->changeColumnName('relationId', 'offerId');
    }

    private function changeColumnName(string $oldName, string $newName): void
    {
        $this->connection->exec(
            "ALTER TABLE labels_relations CHANGE $oldName $newName VARCHAR(255)"
        );
    }
}
