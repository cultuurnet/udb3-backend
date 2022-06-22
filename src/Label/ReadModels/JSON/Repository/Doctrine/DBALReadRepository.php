<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Doctrine;

use CultuurNet\UDB3\Label\ReadModels\Doctrine\AbstractDBALRepository;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ExcludedLabelsRepository;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Query;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ReadModels\Roles\Doctrine\SchemaConfigurator as LabelRolesSchemaConfigurator;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Role\ReadModel\Permissions\Doctrine\SchemaConfigurator as PermissionsSchemaConfigurator;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use CultuurNet\UDB3\StringLiteral;

final class DBALReadRepository extends AbstractDBALRepository implements ReadRepositoryInterface
{
    private StringLiteral $labelRolesTableName;

    private StringLiteral $userRolesTableName;

    private ExcludedLabelsRepository $excludedLabelsRepository;

    public function __construct(
        Connection $connection,
        StringLiteral $tableName,
        StringLiteral $labelRolesTableName,
        StringLiteral $userRolesTableName,
        ExcludedLabelsRepository $excludedLabelsRepository
    ) {
        parent::__construct($connection, $tableName);

        $this->labelRolesTableName = $labelRolesTableName;
        $this->userRolesTableName = $userRolesTableName;
        $this->excludedLabelsRepository = $excludedLabelsRepository;
    }

    public function getByUuid(UUID $uuid): ?Entity
    {
        $aliases = $this->getAliases();
        $whereId = SchemaConfigurator::UUID_COLUMN . ' = ?';

        $queryBuilder = $this->createQueryBuilder()->select($aliases)
            ->from($this->getTableName()->toNative())
            ->where($whereId)
            ->setParameters([$uuid->toString()]);

        return $this->getResult($queryBuilder);
    }

    public function getByName(string $name): ?Entity
    {
        $aliases = $this->getAliases();
        $queryBuilder = $this->createQueryBuilder();
        $likeCondition = $queryBuilder->expr()->like(
            SchemaConfigurator::NAME_COLUMN,
            $queryBuilder->expr()->literal($name)
        );

        $queryBuilder = $queryBuilder->select($aliases)
            ->from($this->getTableName()->toNative())
            ->where($likeCondition)
            ->setParameter(
                SchemaConfigurator::NAME_COLUMN,
                $name
            );

        return $this->getResult($queryBuilder);
    }

    public function canUseLabel(string $userId, string $name): bool
    {
        // A new label is always allowed.
        $label = $this->getByName($name);
        if ($label === null) {
            return true;
        }

        // A public label is always allowed.
        if ($label->getPrivacy()->sameAs(Privacy::PRIVACY_PUBLIC())) {
            return true;
        }

        // A private label is allowed if the user has a role with the label.
        $query = new Query($name, $userId);
        $foundLabels = $this->search($query);

        if ($foundLabels) {
            $nameLowerCase = mb_strtolower($name);
            foreach ($foundLabels as $foundLabel) {
                $foundLabelLowerCase = mb_strtolower($foundLabel->getName()->toString());
                if ($nameLowerCase === $foundLabelLowerCase) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return Entity[]|null
     */
    public function search(Query $query): ?array
    {
        $queryBuilder = $this->createSearchQuery($query);

        $aliases = $this->getAliases();
        $queryBuilder->select($aliases)
            ->orderBy(SchemaConfigurator::NAME_COLUMN);

        if ($query->getOffset()) {
            $queryBuilder
                ->setFirstResult($query->getOffset());
        }

        if ($query->getLimit()) {
            $queryBuilder
                ->setMaxResults($query->getLimit());
        }

        return $this->getResults($queryBuilder);
    }

    public function searchTotalLabels(Query $query): int
    {
        $queryBuilder = $this->createSearchQuery($query);
        $queryBuilder->select('COUNT(*)');

        $statement = $queryBuilder->execute();
        $countArray = $statement->fetch(\PDO::FETCH_NUM);

        return (int) $countArray[0];
    }

    private function createSearchQuery(Query $query): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder();
        $like = $this->createLike($queryBuilder);

        $queryBuilder->from($this->getTableName()->toNative())
            ->where($like)
            ->setParameter(
                SchemaConfigurator::NAME_COLUMN,
                $this->createLikeParameter($query)
            );

        if ($query->isSuggestion()) {
            $queryBuilder->andWhere('name REGEXP \'^[a-zA-Z\d_\-]{2,50}$\'');

            $excludedLabels = $this->excludedLabelsRepository->getAll();
            if (!empty($excludedLabels)) {
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->notIn(
                        SchemaConfigurator::UUID_COLUMN,
                        array_map(
                            fn (string $label) => '"' . $label . '"',
                            $excludedLabels
                        )
                    )
                );
            }
        }

        if ($query->getUserId()) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->orX(
                    // The 'neq' is done on purpose to handle the bit/bool MySQL oddities.
                    $queryBuilder->expr()->neq(
                        SchemaConfigurator::PRIVATE_COLUMN,
                        true
                    ),
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->in(
                            SchemaConfigurator::UUID_COLUMN,
                            $this->createUserLabelsSubQuery()->getSQL()
                        ),
                        // It is possible to add an non private label to a role, this label can be used always.
                        $queryBuilder->expr()->eq(
                            SchemaConfigurator::PRIVATE_COLUMN,
                            true
                        )
                    )
                )
            )->setParameter(
                PermissionsSchemaConfigurator::USER_ID_COLUMN,
                $query->getUserId()
            );
        }

        return $queryBuilder;
    }

    private function createUserLabelsSubQuery(): QueryBuilder
    {
        return $this->createQueryBuilder()
            ->select('DISTINCT ' . LabelRolesSchemaConfigurator::LABEL_ID_COLUMN)
            ->from($this->userRolesTableName->toNative(), 'ur')
            ->innerJoin(
                'ur',
                $this->labelRolesTableName->toNative(),
                'lr',
                'ur.' . PermissionsSchemaConfigurator::ROLE_ID_COLUMN . ' = lr.' . LabelRolesSchemaConfigurator::ROLE_ID_COLUMN
            )
            ->where('ur.' . PermissionsSchemaConfigurator::USER_ID_COLUMN . '= :' . PermissionsSchemaConfigurator::USER_ID_COLUMN);
    }

    /**
     * @return string[]
     */
    private function getAliases(): array
    {
        return [
            SchemaConfigurator::UUID_COLUMN,
            SchemaConfigurator::NAME_COLUMN,
            SchemaConfigurator::VISIBLE_COLUMN,
            SchemaConfigurator::PRIVATE_COLUMN,
            SchemaConfigurator::PARENT_UUID_COLUMN,
            SchemaConfigurator::COUNT_COLUMN,
        ];
    }

    private function createLike(QueryBuilder $queryBuilder): string
    {
        return $queryBuilder->expr()->like(
            SchemaConfigurator::NAME_COLUMN,
            ':' . SchemaConfigurator::NAME_COLUMN
        );
    }

    private function createLikeParameter(Query $query): string
    {
        return '%' . $query->getValue() . '%';
    }

    private function getResult(QueryBuilder $queryBuilder): ?Entity
    {
        $entity = null;

        $statement = $queryBuilder->execute();
        $row = $statement->fetch(\PDO::FETCH_ASSOC);
        if ($row) {
            $entity = $this->rowToEntity($row);
        }

        return $entity;
    }

    /**
     * @return Entity[]|null
     */
    private function getResults(QueryBuilder $queryBuilder): ?array
    {
        $entities = null;

        $statement = $queryBuilder->execute();
        $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $entities[] = $this->rowToEntity($row);
        }

        return $entities;
    }

    private function rowToEntity(array $row): Entity
    {
        $uuid = new UUID($row[SchemaConfigurator::UUID_COLUMN]);

        $name = new LabelName($row[SchemaConfigurator::NAME_COLUMN]);

        $visibility = $row[SchemaConfigurator::VISIBLE_COLUMN]
            ? Visibility::VISIBLE() : Visibility::INVISIBLE();

        $privacy = $row[SchemaConfigurator::PRIVATE_COLUMN]
            ? Privacy::PRIVACY_PRIVATE() : Privacy::PRIVACY_PUBLIC();

        $parentUuid = $row[SchemaConfigurator::PARENT_UUID_COLUMN]
            ? new UUID($row[SchemaConfigurator::PARENT_UUID_COLUMN]) : null;


        $count = (int) $row[SchemaConfigurator::COUNT_COLUMN];

        return new Entity(
            $uuid,
            $name,
            $visibility,
            $privacy,
            $parentUuid,
            $count,
            $this->isExcluded($uuid)
        );
    }

    private function isExcluded(UUID $uuid): bool
    {
        return \in_array($uuid->toString(), $this->excludedLabelsRepository->getAll(), true);
    }
}
