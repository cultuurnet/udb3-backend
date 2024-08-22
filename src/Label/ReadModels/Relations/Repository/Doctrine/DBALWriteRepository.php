<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\Relations\Repository\Doctrine;

use CultuurNet\UDB3\Label\ReadModels\Doctrine\AbstractDBALRepository;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\WriteRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\RelationType;
use Doctrine\DBAL\Query\QueryBuilder;

class DBALWriteRepository extends AbstractDBALRepository implements WriteRepositoryInterface
{
    public function save(
        string $labelName,
        RelationType $relationType,
        string $relationId,
        bool $imported
    ): void {
        $queryBuilder = $this->createQueryBuilder()
            ->insert($this->getTableName())
            ->values([
                ColumnNames::LABEL_NAME => '?',
                ColumnNames::RELATION_TYPE => '?',
                ColumnNames::RELATION_ID => '?',
                ColumnNames::IMPORTED => '?',
            ])
            ->setParameters([
                $labelName,
                $relationType->toString(),
                $relationId,
                $imported ? 1 : 0,
            ]);

        $this->executeTransactional($queryBuilder);
    }

    public function deleteByLabelNameAndRelationId(
        string $labelName,
        string $relationId
    ): void {
        $queryBuilder = $this->createQueryBuilder()
            ->delete($this->getTableName())
            ->where(ColumnNames::LABEL_NAME . ' = ?')
            ->andWhere(ColumnNames::RELATION_ID . ' = ?')
            ->setParameters([$labelName, $relationId]);

        $this->executeTransactional($queryBuilder);
    }

    public function deleteImportedByRelationId(string $relationId): void
    {
        $queryBuilder = $this->createQueryBuilder()
            ->delete($this->getTableName())
            ->where(ColumnNames::RELATION_ID . ' = :relationId')
            ->andWhere(ColumnNames::IMPORTED . ' = :imported')
            ->setParameters(
                [
                    ':relationId' => $relationId,
                    ':imported' => true,
                ]
            );

        $this->executeTransactional($queryBuilder);
    }

    private function executeTransactional(QueryBuilder $queryBuilder): void
    {
        $this->getConnection()->transactional(function () use ($queryBuilder): void {
            $queryBuilder->execute();
        });
    }
}
