<?php

namespace CultuurNet\UDB3\Label\ReadModels\Relations\Repository\Doctrine;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\LabelRelation;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

abstract class BaseDBALRepositoryTest extends TestCase
{
    use DBALTestConnectionTrait;

    /**
     * @var StringLiteral
     */
    private $tableName;

    protected function setUp()
    {
        $this->tableName = new StringLiteral('test_places_relations');

        $schemaConfigurator = new SchemaConfigurator($this->tableName);

        $schemaManager = $this->getConnection()->getSchemaManager();

        $schemaConfigurator->configure($schemaManager);
    }

    /**
     * @return StringLiteral
     */
    protected function getTableName()
    {
        return $this->tableName;
    }

    /**
     * @param LabelRelation $labelRelation
     */
    protected function saveLabelRelation(LabelRelation $labelRelation)
    {
        $values = $this->labelRelationToValues($labelRelation);

        $sql = 'INSERT INTO ' . $this->tableName . ' VALUES (?, ?, ?, ?)';

        $this->connection->executeQuery($sql, $values);
    }

    /**
     * @param LabelRelation $offerLabelRelation
     * @return array
     */
    protected function labelRelationToValues(LabelRelation $offerLabelRelation)
    {
        return [
            $offerLabelRelation->getLabelName()->toNative(),
            $offerLabelRelation->getRelationType()->toNative(),
            $offerLabelRelation->getRelationId(),
            $offerLabelRelation->isImported(),
        ];
    }

    /**
     * @return LabelRelation[]
     */
    protected function getLabelRelations()
    {
        $sql = 'SELECT * FROM ' . $this->tableName;

        $statement = $this->connection->executeQuery($sql);
        $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $labelRelations = [];
        foreach ($rows as $row) {
            $labelRelations[] = LabelRelation::fromRelationalData($row);
        }

        return $labelRelations;
    }
}
