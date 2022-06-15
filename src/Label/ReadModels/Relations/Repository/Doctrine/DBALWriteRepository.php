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
                SchemaConfigurator::LABEL_NAME => '?',
                SchemaConfigurator::RELATION_TYPE => '?',
                SchemaConfigurator::RELATION_ID => '?',
                SchemaConfigurator::IMPORTED => '?',
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
            ->where(SchemaConfigurator::LABEL_NAME . ' = ?')
            ->andWhere(SchemaConfigurator::RELATION_ID . ' = ?')
            ->setParameters([$labelName, $relationId]);

        $this->executeTransactional($queryBuilder);
    }

    public function deleteImportedByRelationId(string $relationId): void
    {
        $queryBuilder = $this->createQueryBuilder()
            ->delete($this->getTableName())
            ->where(SchemaConfigurator::RELATION_ID . ' = :relationId')
            ->andWhere(SchemaConfigurator::IMPORTED . ' = :imported')
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
        $this->getConnection()->transactional(function () use ($queryBuilder) {
            $queryBuilder->execute();
        });
    }
}
