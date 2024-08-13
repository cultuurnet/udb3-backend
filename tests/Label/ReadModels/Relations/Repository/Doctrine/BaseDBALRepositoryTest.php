<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\Relations\Repository\Doctrine;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\LabelRelation;
use PDO;
use PHPUnit\Framework\TestCase;

abstract class BaseDBALRepositoryTest extends TestCase
{
    use DBALTestConnectionTrait;

    private string $tableName;

    protected function setUp(): void
    {
        $this->tableName = 'test_places_relations';

        $schemaConfigurator = new SchemaConfigurator($this->tableName);

        $schemaManager = $this->getConnection()->getSchemaManager();

        $schemaConfigurator->configure($schemaManager);
    }

    protected function getTableName(): string
    {
        return $this->tableName;
    }

    protected function saveLabelRelation(LabelRelation $labelRelation): void
    {
        $values = $this->labelRelationToValues($labelRelation);

        $sql = 'INSERT INTO ' . $this->tableName . ' VALUES (?, ?, ?, ?)';

        $this->connection->executeQuery($sql, $values, [
            PDO::PARAM_STR,
            PDO::PARAM_STR,
            PDO::PARAM_STR,
            PDO::PARAM_BOOL,
        ]);
    }

    protected function labelRelationToValues(LabelRelation $offerLabelRelation): array
    {
        return [
            $offerLabelRelation->getLabelName(),
            $offerLabelRelation->getRelationType()->toString(),
            $offerLabelRelation->getRelationId(),
            $offerLabelRelation->isImported(),
        ];
    }

    /**
     * @return LabelRelation[]
     */
    protected function getLabelRelations(): array
    {
        $sql = 'SELECT * FROM ' . $this->tableName;

        $statement = $this->connection->executeQuery($sql);
        $rows = $statement->fetchAllAssociative();

        $labelRelations = [];
        foreach ($rows as $row) {
            $labelRelations[] = LabelRelation::fromRelationalData($row);
        }

        return $labelRelations;
    }
}
