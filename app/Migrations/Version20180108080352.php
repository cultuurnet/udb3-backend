<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20180108080352 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->getLabelsRelationsTable($schema)
            ->addIndex(
                [
                    'relationId',
                ],
                'IDX_RELATION_ID'
            );
    }


    public function down(Schema $schema): void
    {
        $this->getLabelsRelationsTable($schema)
            ->dropIndex('IDX_RELATION_ID');
    }

    /**
     * @return \Doctrine\DBAL\Schema\Table
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    private function getLabelsRelationsTable(Schema $schema)
    {
        return $schema->getTable('labels_relations');
    }
}
