<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\Relations\Repository\Doctrine;

use CultuurNet\UDB3\Label\ReadModels\Doctrine\AbstractDBALRepository;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\LabelRelation;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\RelationType;

class DBALReadRepository extends AbstractDBALRepository implements ReadRepositoryInterface
{
    /**
     * @inheritdoc
     */
    public function getLabelRelations(string $labelName)
    {
        $aliases = $this->getAliases();
        $whereLabelName = SchemaConfigurator::LABEL_NAME . ' = ?';

        $queryBuilder = $this->createQueryBuilder()->select($aliases)
            ->from($this->getTableName()->toNative())
            ->where($whereLabelName)
            ->setParameters([$labelName]);

        $statement = $queryBuilder->execute();

        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $labelRelation = LabelRelation::fromRelationalData($row);
            yield $labelRelation;
        }
    }

    public function getLabelRelationsForType(string $labelName, RelationType $relationType): array
    {
        $whereLabelName = SchemaConfigurator::LABEL_NAME . ' = ?';

        return $this->createQueryBuilder()->select(SchemaConfigurator::RELATION_ID)
            ->from($this->getTableName()->toNative())
            ->where($whereLabelName)
            ->andWhere(SchemaConfigurator::RELATION_TYPE . ' = ?')
            ->setParameters([$labelName, $relationType->toString()])
        ->execute()->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * @inheritdoc
     */
    public function getLabelRelationsForItem(string $relationId): array
    {
        $aliases = $this->getAliases();
        $whereRelationId = SchemaConfigurator::RELATION_ID . ' = ?';

        $queryBuilder = $this->createQueryBuilder()->select($aliases)
            ->from($this->getTableName()->toNative())
            ->where($whereRelationId)
            ->setParameters(
                [
                    $relationId,
                ]
            );

        $statement = $queryBuilder->execute();

        $labelRelations = [];
        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $labelRelations[] = LabelRelation::fromRelationalData($row);
        }

        return $labelRelations;
    }

    private function getAliases(): array
    {
        return [
            SchemaConfigurator::LABEL_NAME,
            SchemaConfigurator::RELATION_TYPE,
            SchemaConfigurator::RELATION_ID,
            SchemaConfigurator::IMPORTED,
        ];
    }
}
