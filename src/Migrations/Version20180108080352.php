<?php

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180108080352 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->getLabelsRelationsTable($schema)
            ->addIndex(
                [
                    'relationId'
                ],
                'IDX_RELATION_ID'
            );

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->getLabelsRelationsTable($schema)
            ->dropIndex('IDX_RELATION_ID');
    }

    /**
     * @param Schema $schema
     * @return \Doctrine\DBAL\Schema\Table
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    private function getLabelsRelationsTable(Schema $schema)
    {
        return $schema->getTable('labels_relations');
    }
}
