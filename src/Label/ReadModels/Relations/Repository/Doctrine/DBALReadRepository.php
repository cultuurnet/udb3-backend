<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\Relations\Repository\Doctrine;

use CultuurNet\UDB3\Label\ReadModels\Doctrine\AbstractDBALRepository;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\LabelRelation;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\RelationType;

class DBALReadRepository extends AbstractDBALRepository implements ReadRepositoryInterface
{
    public function getLabelRelations(string $labelName)
    {
        $aliases = $this->getAliases();
        $whereLabelName = ColumnNames::LABEL_NAME . ' = ?';

        $queryBuilder = $this->createQueryBuilder()->select($aliases)
            ->from($this->getTableName())
            ->where($whereLabelName)
            ->setParameters([$labelName]);

        $statement = $queryBuilder->execute();

        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $labelRelation = LabelRelation::fromRelationalData($row);
            yield $labelRelation;
        }
    }

    public function getLabelsRelationsForType(array $labelNames, RelationType $relationType): array
    {
       if (empty($labelNames)) {
            return [];
        }

        return $this->createQueryBuilder()->select(ColumnNames::RELATION_ID)
            ->from($this->getTableName())
            ->where($this->createQueryBuilder()->expr()->in(ColumnNames::LABEL_NAME, ':labelNames'))
            ->andWhere(ColumnNames::RELATION_TYPE . ' = :relationType')
            ->setParameters([
                'labelNames' => $labelNames,
                'relationType' => $relationType->toString()
            ])
            ->execute()
            ->fetchFirstColumn();
    }

    public function getLabelRelationsForType(string $labelName, RelationType $relationType): array
    {
        $whereLabelName = ColumnNames::LABEL_NAME . ' = ?';

        return $this->createQueryBuilder()->select(ColumnNames::RELATION_ID)
            ->from($this->getTableName())
            ->where($whereLabelName)
            ->andWhere(ColumnNames::RELATION_TYPE . ' = ?')
            ->setParameters([$labelName, $relationType->toString()])
            ->execute()
            ->fetchFirstColumn();
    }

    public function getLabelRelationsForItem(string $relationId): array
    {
        $aliases = $this->getAliases();
        $whereRelationId = ColumnNames::RELATION_ID . ' = ?';

        $queryBuilder = $this->createQueryBuilder()->select($aliases)
            ->from($this->getTableName())
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
            ColumnNames::LABEL_NAME,
            ColumnNames::RELATION_TYPE,
            ColumnNames::RELATION_ID,
            ColumnNames::IMPORTED,
        ];
    }
}
