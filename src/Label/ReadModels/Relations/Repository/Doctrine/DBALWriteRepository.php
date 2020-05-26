<?php

namespace CultuurNet\UDB3\Label\ReadModels\Relations\Repository\Doctrine;

use CultuurNet\UDB3\Label\ReadModels\Doctrine\AbstractDBALRepository;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\WriteRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\LabelName;
use CultuurNet\UDB3\Label\ValueObjects\RelationType;
use Doctrine\DBAL\Query\QueryBuilder;
use ValueObjects\StringLiteral\StringLiteral;

class DBALWriteRepository extends AbstractDBALRepository implements WriteRepositoryInterface
{
    /**
     * @inheritdoc
     */
    public function save(
        LabelName $labelName,
        RelationType $relationType,
        StringLiteral $relationId,
        $imported
    ) {
        $queryBuilder = $this->createQueryBuilder()
            ->insert($this->getTableName())
            ->values([
                SchemaConfigurator::LABEL_NAME => '?',
                SchemaConfigurator::RELATION_TYPE => '?',
                SchemaConfigurator::RELATION_ID => '?',
                SchemaConfigurator::IMPORTED => '?',
            ])
            ->setParameters([
                $labelName->toNative(),
                $relationType->toNative(),
                $relationId->toNative(),
                $imported ? 1 : 0,
            ]);

        $this->executeTransactional($queryBuilder);
    }

    /**
     * @inheritdoc
     */
    public function deleteByLabelNameAndRelationId(
        LabelName $labelName,
        StringLiteral $relationId
    ) {
        $queryBuilder = $this->createQueryBuilder()
            ->delete($this->getTableName())
            ->where(SchemaConfigurator::LABEL_NAME . ' = ?')
            ->andWhere(SchemaConfigurator::RELATION_ID . ' = ?')
            ->setParameters([$labelName->toNative(), $relationId->toNative()]);

        $this->executeTransactional($queryBuilder);
    }

    /**
     * @inheritdoc
     */
    public function deleteByRelationId(StringLiteral $relationId)
    {
        $queryBuilder = $this->createQueryBuilder()
            ->delete($this->getTableName())
            ->where(SchemaConfigurator::RELATION_ID . ' = ?')
            ->setParameters([$relationId->toNative()]);

        $this->executeTransactional($queryBuilder);
    }

    /**
     * @inheritdoc
     */
    public function deleteImportedByRelationId(StringLiteral $relationId)
    {
        $queryBuilder = $this->createQueryBuilder()
            ->delete($this->getTableName())
            ->where(SchemaConfigurator::RELATION_ID . ' = :relationId')
            ->andWhere(SchemaConfigurator::IMPORTED . ' = :imported')
            ->setParameters(
                [
                    ':relationId' => $relationId->toNative(),
                    ':imported' => true,
                ]
            );

        $this->executeTransactional($queryBuilder);
    }

    /**
     * @param QueryBuilder $queryBuilder
     */
    private function executeTransactional(QueryBuilder $queryBuilder)
    {
        $this->getConnection()->transactional(function () use ($queryBuilder) {
            $queryBuilder->execute();
        });
    }
}
