<?php

namespace CultuurNet\UDB3\Silex\Migrations;

use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\Doctrine\SchemaConfigurator;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160630102019 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addNameToLabelRelations($schema);

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema
            ->getTable(Version20160607134416::LABELS_RELATIONS_TABLE)
            ->dropColumn(SchemaConfigurator::LABEL_NAME_COLUMN);
    }

    /**
     * @param Schema $schema
     */
    private function addNameToLabelRelations(Schema $schema)
    {
        $labelRelationsTable = $schema->getTable(Version20160607134416::LABELS_RELATIONS_TABLE);

        $labelRelationsTable
            ->addColumn(SchemaConfigurator::LABEL_NAME_COLUMN, Type::STRING)
            ->setLength(255)
            ->setNotnull(true);

        $labelRelationsTable->addUniqueIndex([SchemaConfigurator::LABEL_NAME_COLUMN]);
    }
}
