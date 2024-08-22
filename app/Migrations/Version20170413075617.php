<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\Doctrine\ColumnNames;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

class Version20170413075617 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->getLabelsRelationsTable($schema)
            ->addColumn(ColumnNames::IMPORTED, Types::BOOLEAN)
            ->setNotnull(true)
            ->setDefault(false);
    }


    public function down(Schema $schema): void
    {
        $this->getLabelsRelationsTable($schema)
            ->dropColumn(ColumnNames::IMPORTED);
    }

    private function getLabelsRelationsTable(Schema $schema): Table
    {
        return $schema->getTable('labels_relations');
    }
}
