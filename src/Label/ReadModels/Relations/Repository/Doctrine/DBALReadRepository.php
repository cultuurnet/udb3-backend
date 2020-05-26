<?php

namespace CultuurNet\UDB3\Label\ReadModels\Relations\Repository\Doctrine;

use CultuurNet\UDB3\Label\ReadModels\Doctrine\AbstractDBALRepository;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\LabelRelation;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\LabelName;
use ValueObjects\StringLiteral\StringLiteral;

class DBALReadRepository extends AbstractDBALRepository implements ReadRepositoryInterface
{
    /**
     * @inheritdoc
     */
    public function getLabelRelations(LabelName $labelName)
    {
        $aliases = $this->getAliases();
        $whereLabelName = SchemaConfigurator::LABEL_NAME . ' = ?';

        $queryBuilder = $this->createQueryBuilder()->select($aliases)
            ->from($this->getTableName()->toNative())
            ->where($whereLabelName)
            ->setParameters([$labelName->toNative()]);

        $statement = $queryBuilder->execute();

        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $labelRelation = LabelRelation::fromRelationalData($row);
            yield $labelRelation;
        }
    }

    /**
     * @inheritdoc
     */
    public function getLabelRelationsForItem(StringLiteral $relationId)
    {
        $aliases = $this->getAliases();
        $whereRelationId = SchemaConfigurator::RELATION_ID . ' = ?';

        $queryBuilder = $this->createQueryBuilder()->select($aliases)
            ->from($this->getTableName()->toNative())
            ->where($whereRelationId)
            ->setParameters(
                [
                    $relationId->toNative(),
                ]
            );

        $statement = $queryBuilder->execute();

        $labelRelations = [];
        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $labelRelations[] = LabelRelation::fromRelationalData($row);
        }

        return $labelRelations;
    }

    /**
     * @return array
     */
    private function getAliases()
    {
        return [
            SchemaConfigurator::LABEL_NAME,
            SchemaConfigurator::RELATION_TYPE,
            SchemaConfigurator::RELATION_ID,
            SchemaConfigurator::IMPORTED,
        ];
    }
}
