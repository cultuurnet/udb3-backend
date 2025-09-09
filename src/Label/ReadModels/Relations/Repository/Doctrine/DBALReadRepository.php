<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\Relations\Repository\Doctrine;

use CultuurNet\UDB3\Label\ReadModels\Doctrine\AbstractDBALRepository;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\LabelRelation;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\RelationType;
use Doctrine\DBAL\Connection;

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

        $qb = $this->createQueryBuilder();

        return $qb->select(ColumnNames::RELATION_ID)
            ->from($this->getTableName())
            ->where(
                $qb->expr()->in(
                    ColumnNames::LABEL_NAME,
                    $qb->createNamedParameter($labelNames, Connection::PARAM_STR_ARRAY)
                )
            )
            ->andWhere($qb->expr()->eq(ColumnNames::RELATION_TYPE, ':relationType'))
            ->setParameter('relationType', $relationType->toString())
            ->execute()
            ->fetchFirstColumn();
    }

    // Alias for single label lookup
    public function getLabelRelationsForType(string $labelName, RelationType $relationType): array
    {
        return $this->getLabelsRelationsForType([$labelName], $relationType);
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
