<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Doctrine;

use CultuurNet\UDB3\Label\ReadModels\Doctrine\AbstractDBALRepository;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Query;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ReadModels\Roles\Doctrine\ColumnNames as LabelRolesColumnNames;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Role\ReadModel\Permissions\Doctrine\ColumnNames as PermissionsColumnNames;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

final class DBALReadRepository extends AbstractDBALRepository implements ReadRepositoryInterface
{
    private const MAX_RESULTS = 30;

    private string $labelRolesTableName;

    private string $userRolesTableName;

    public function __construct(
        Connection $connection,
        string $tableName,
        string $labelRolesTableName,
        string $userRolesTableName
    ) {
        parent::__construct($connection, $tableName);

        $this->labelRolesTableName = $labelRolesTableName;
        $this->userRolesTableName = $userRolesTableName;
    }

    public function getByUuid(Uuid $uuid): ?Entity
    {
        $aliases = $this->getAliases();
        $whereId = ColumnNames::UUID_COLUMN . ' = ?';

        $queryBuilder = $this->createQueryBuilder()->select($aliases)
            ->from($this->getTableName())
            ->where($whereId)
            ->setParameters([$uuid->toString()]);

        return $this->getResult($queryBuilder);
    }

    public function getByName(string $name): ?Entity
    {
        $aliases = $this->getAliases();
        $queryBuilder = $this->createQueryBuilder();
        $likeCondition = $queryBuilder->expr()->like(
            ColumnNames::NAME_COLUMN,
            $queryBuilder->expr()->literal($name)
        );

        $queryBuilder = $queryBuilder->select($aliases)
            ->from($this->getTableName())
            ->where($likeCondition)
            ->setParameter(
                ColumnNames::NAME_COLUMN,
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
        if ($label->getPrivacy()->sameAs(Privacy::public())) {
            return true;
        }

        // A private label is allowed if the user has a role with the label.
        $query = new Query($name, $userId);
        $foundLabels = $this->search($query);

        $nameLowerCase = mb_strtolower($name);
        foreach ($foundLabels as $foundLabel) {
            $foundLabelLowerCase = mb_strtolower($foundLabel->getName());
            if ($nameLowerCase === $foundLabelLowerCase) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return Entity[]
     */
    public function search(Query $query): array
    {
        $queryBuilder = $this->createSearchQuery($query);

        $aliases = $this->getAliases();
        $queryBuilder->select($aliases)
            ->addSelect(
                '(CASE 
               WHEN name = :exactMatch THEN 1
               WHEN name LIKE :startMatch THEN 2
               WHEN name LIKE :partialMatch THEN 3
               ELSE 4
               END) AS sorted'
            )
            ->setParameter('exactMatch', $query->getValue())
            ->setParameter('startMatch', $query->getValue() . '%')
            ->setParameter('partialMatch', $this->createLikeParameter($query))
            ->orderBy('sorted', 'ASC')
            ->addOrderBy(ColumnNames::NAME_COLUMN, 'ASC');

        if ($query->getOffset()) {
            $queryBuilder
                ->setFirstResult($query->getOffset());
        }

        $queryBuilder
            ->setMaxResults($query->getLimit() ?? self::MAX_RESULTS);

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

        $queryBuilder->from($this->getTableName())
            ->where($like)
            ->setParameter(
                ColumnNames::NAME_COLUMN,
                $this->createLikeParameter($query)
            );

        if ($query->isSuggestion()) {
            $queryBuilder->andWhere(ColumnNames::EXCLUDED_COLUMN . ' = :excluded')
                ->setParameter(':excluded', 0);
        }

        if ($query->getUserId()) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->orX(
                    // The 'neq' is done on purpose to handle the bit/bool MySQL oddities.
                    $queryBuilder->expr()->neq(
                        ColumnNames::PRIVATE_COLUMN,
                        true
                    ),
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->in(
                            ColumnNames::UUID_COLUMN,
                            $this->createUserLabelsSubQuery()->getSQL()
                        ),
                        // It is possible to add an non private label to a role, this label can be used always.
                        $queryBuilder->expr()->eq(
                            ColumnNames::PRIVATE_COLUMN,
                            true
                        )
                    )
                )
            )->setParameter(
                PermissionsColumnNames::USER_ID_COLUMN,
                $query->getUserId()
            );
        }

        return $queryBuilder;
    }

    private function createUserLabelsSubQuery(): QueryBuilder
    {
        return $this->createQueryBuilder()
            ->select('DISTINCT ' . LabelRolesColumnNames::LABEL_ID_COLUMN)
            ->from($this->userRolesTableName, 'ur')
            ->innerJoin(
                'ur',
                $this->labelRolesTableName,
                'lr',
                'ur.' . PermissionsColumnNames::ROLE_ID_COLUMN . ' = lr.' . LabelRolesColumnNames::ROLE_ID_COLUMN
            )
            ->where('ur.' . PermissionsColumnNames::USER_ID_COLUMN . '= :' . PermissionsColumnNames::USER_ID_COLUMN);
    }

    /**
     * @return string[]
     */
    private function getAliases(): array
    {
        return [
            ColumnNames::UUID_COLUMN,
            ColumnNames::NAME_COLUMN,
            ColumnNames::VISIBLE_COLUMN,
            ColumnNames::PRIVATE_COLUMN,
            ColumnNames::EXCLUDED_COLUMN,
        ];
    }

    private function createLike(QueryBuilder $queryBuilder): string
    {
        return $queryBuilder->expr()->like(
            ColumnNames::NAME_COLUMN,
            ':' . ColumnNames::NAME_COLUMN
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
     * @return Entity[]
     */
    private function getResults(QueryBuilder $queryBuilder): array
    {
        $entities = [];

        $statement = $queryBuilder->execute();
        $rows = $statement->fetchAllAssociative();
        foreach ($rows as $row) {
            $entities[] = $this->rowToEntity($row);
        }

        return $entities;
    }

    private function rowToEntity(array $row): Entity
    {
        $uuid = new Uuid($row[ColumnNames::UUID_COLUMN]);

        $name = $row[ColumnNames::NAME_COLUMN];

        $visibility = $row[ColumnNames::VISIBLE_COLUMN]
            ? Visibility::visible() : Visibility::invisible();

        $privacy = $row[ColumnNames::PRIVATE_COLUMN]
            ? Privacy::private() : Privacy::public();

        $excluded =  (bool) $row[ColumnNames::EXCLUDED_COLUMN];

        return new Entity(
            $uuid,
            $name,
            $visibility,
            $privacy,
            $excluded
        );
    }
}
