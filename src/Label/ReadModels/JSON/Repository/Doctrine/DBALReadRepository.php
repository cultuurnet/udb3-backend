<?php

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Doctrine;

use CultuurNet\UDB3\Label\ReadModels\Doctrine\AbstractDBALRepository;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Query;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ReadModels\Roles\Doctrine\SchemaConfigurator as LabelRolesSchemaConfigurator;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Role\ReadModel\Permissions\Doctrine\SchemaConfigurator as PermissionsSchemaConfigurator;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use ValueObjects\Identity\UUID;
use ValueObjects\Number\Natural;
use ValueObjects\StringLiteral\StringLiteral;

class DBALReadRepository extends AbstractDBALRepository implements ReadRepositoryInterface
{
    /**
     * @var StringLiteral
     */
    private $labelRolesTableName;

    /**
     * @var StringLiteral
     */
    private $userRolesTableName;

    /**
     * DBALReadRepository constructor.
     * @param Connection $connection
     * @param StringLiteral $tableName
     * @param StringLiteral $labelRolesTableName
     * @param StringLiteral $userRolesTableName
     */
    public function __construct(
        Connection $connection,
        StringLiteral $tableName,
        StringLiteral $labelRolesTableName,
        StringLiteral $userRolesTableName
    ) {
        parent::__construct($connection, $tableName);

        $this->labelRolesTableName = $labelRolesTableName;
        $this->userRolesTableName = $userRolesTableName;
    }

    /**
     * @param UUID $uuid
     * @return Entity|null
     */
    public function getByUuid(UUID $uuid)
    {
        $aliases = $this->getAliases();
        $whereId = SchemaConfigurator::UUID_COLUMN . ' = ?';

        $queryBuilder = $this->createQueryBuilder()->select($aliases)
            ->from($this->getTableName()->toNative())
            ->where($whereId)
            ->setParameters([$uuid]);

        return $this->getResult($queryBuilder);
    }

    /**
     * @param StringLiteral $name
     * @return Entity|null
     */
    public function getByName(StringLiteral $name)
    {
        $aliases = $this->getAliases();
        $queryBuilder = $this->createQueryBuilder();
        $likeCondition = $queryBuilder->expr()->like(
            SchemaConfigurator::NAME_COLUMN,
            $queryBuilder->expr()->literal($name->toNative())
        );

        $queryBuilder = $queryBuilder->select($aliases)
            ->from($this->getTableName()->toNative())
            ->where($likeCondition)
            ->setParameter(
                SchemaConfigurator::NAME_COLUMN,
                $name->toNative()
            );

        return $this->getResult($queryBuilder);
    }

    /**
     * @param StringLiteral $userId
     * @param StringLiteral $name
     * @return bool
     */
    public function canUseLabel(StringLiteral $userId, StringLiteral $name)
    {
        // A new label is always allowed.
        $label = $this->getByName($name);
        if ($label === null) {
            return true;
        }

        // A public label is always allowed.
        if ($label->getPrivacy() === Privacy::PRIVACY_PUBLIC()) {
            return true;
        }

        // A private label is allowed if the user has a role with the label.
        $query = new Query($name, $userId);
        $foundLabels = $this->search($query);

        if ($foundLabels) {
            $nameLowerCase = mb_strtolower($name->toNative());
            foreach ($foundLabels as $foundLabel) {
                $foundLabelLowerCase = mb_strtolower($foundLabel->getName()->toNative());
                if ($nameLowerCase === $foundLabelLowerCase) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param Query $query
     * @return Entity[]|null
     */
    public function search(Query $query)
    {
        $queryBuilder = $this->createSearchQuery($query);

        $aliases = $this->getAliases();
        $queryBuilder->select($aliases)
            ->orderBy(SchemaConfigurator::NAME_COLUMN);

        if ($query->getOffset()) {
            $queryBuilder
                ->setFirstResult($query->getOffset()->toNative());
        }

        if ($query->getLimit()) {
            $queryBuilder
                ->setMaxResults($query->getLimit()->toNative());
        }

        return $this->getResults($queryBuilder);
    }

    /**
     * @param Query $query
     * @return Natural
     */
    public function searchTotalLabels(Query $query)
    {
        $queryBuilder = $this->createSearchQuery($query);
        $queryBuilder->select('COUNT(*)');

        $statement = $queryBuilder->execute();
        $countArray = $statement->fetch(\PDO::FETCH_NUM);

        return new Natural($countArray[0]);
    }

    /**
     * @param Query $query
     * @return QueryBuilder
     */
    private function createSearchQuery(Query $query)
    {
        $queryBuilder = $this->createQueryBuilder();
        $like = $this->createLike($queryBuilder);

        $queryBuilder->from($this->getTableName()->toNative())
            ->where($like)
            ->setParameter(
                SchemaConfigurator::NAME_COLUMN,
                $this->createLikeParameter($query)
            );

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
                            $this->createUserLabelsSubQuery($query)->getSQL()
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
                $query->getUserId()->toNative()
            );
        }

        return $queryBuilder;
    }

    /**
     * @param Query $query
     * @return QueryBuilder
     */
    private function createUserLabelsSubQuery(Query $query)
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
     * @return array
     */
    private function getAliases()
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

    /**
     * @param QueryBuilder $queryBuilder
     * @return string
     */
    private function createLike(QueryBuilder $queryBuilder)
    {
        return $queryBuilder->expr()->like(
            SchemaConfigurator::NAME_COLUMN,
            ':' . SchemaConfigurator::NAME_COLUMN
        );
    }

    /**
     * @param Query $query
     * @return string
     */
    private function createLikeParameter(Query $query)
    {
        return '%' . $query->getValue()->toNative() . '%';
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @return Entity|null
     */
    private function getResult(QueryBuilder $queryBuilder)
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
     * @param QueryBuilder $queryBuilder
     * @return Entity[]|null
     */
    private function getResults(QueryBuilder $queryBuilder)
    {
        $entities = null;

        $statement = $queryBuilder->execute();
        $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $entities[] = $this->rowToEntity($row);
        }

        return $entities;
    }

    /**
     * @param array $row
     * @return Entity
     */
    private function rowToEntity(array $row)
    {
        $uuid = new UUID($row[SchemaConfigurator::UUID_COLUMN]);

        $name = new StringLiteral($row[SchemaConfigurator::NAME_COLUMN]);

        $visibility = $row[SchemaConfigurator::VISIBLE_COLUMN]
            ? Visibility::VISIBLE() : Visibility::INVISIBLE();

        $privacy = $row[SchemaConfigurator::PRIVATE_COLUMN]
            ? Privacy::PRIVACY_PRIVATE() : Privacy::PRIVACY_PUBLIC();

        $parentUuid = new UUID($row[SchemaConfigurator::PARENT_UUID_COLUMN]);

        $count = new Natural($row[SchemaConfigurator::COUNT_COLUMN]);

        return new Entity(
            $uuid,
            $name,
            $visibility,
            $privacy,
            $parentUuid,
            $count
        );
    }
}
