<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\Repositories\Search;

use CultuurNet\UDB3\Http\Ownership\Search\SearchQuery;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Ownership\OwnershipState;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItemCollection;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItemNotFound;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

final class DBALOwnershipSearchRepository implements OwnershipSearchRepository
{
    private Connection $connection;

    private const URL_PARAMETER_TO_COLUMN = [
        'itemId' => 'item_id',
        'state' => 'state',
        'ownerId' => 'owner_id',
        'itemType' => 'item_type',
    ];

    private const URL_PARAMETER_WITH_MULTIPLE_VALUES = [
        'state',
    ];

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function save(OwnershipItem $ownershipSearchItem): void
    {
        $this->connection->insert('ownership_search', [
            'id' => $ownershipSearchItem->getId(),
            'item_id' => $ownershipSearchItem->getItemId(),
            'item_type' => $ownershipSearchItem->getItemType(),
            'owner_id' => $ownershipSearchItem->getOwnerId(),
            'state' => $ownershipSearchItem->getState(),
            'role_id' => $ownershipSearchItem->getRoleId() ? $ownershipSearchItem->getRoleId()->toString() : null,
        ]);
    }

    public function updateState(string $id, OwnershipState $state): void
    {
        $this->connection->update(
            'ownership_search',
            ['state' => $state->toString()],
            ['id' => $id]
        );
    }

    public function updateRoleId(string $id, ?Uuid $roleId): void
    {
        $this->connection->update(
            'ownership_search',
            ['role_id' => $roleId ? $roleId->toString() : null],
            ['id' => $id]
        );
    }

    public function getById(string $id): OwnershipItem
    {
        $ownershipSearchRow = $this->connection->createQueryBuilder()
            ->select('*')
            ->from('ownership_search')
            ->where('id = :id')
            ->setParameter('id', $id)
            ->execute()
            ->fetchAssociative();

        if (!$ownershipSearchRow) {
            throw OwnershipItemNotFound::byId($id);
        }

        return $this->createOwnershipSearchItem($ownershipSearchRow);
    }

    public function search(SearchQuery $searchQuery): OwnershipItemCollection
    {
        $queryBuilder = $this->createSearchQueryBuilder($searchQuery)
            ->select('*');

        if ($searchQuery->getStart()) {
            $queryBuilder->setFirstResult($searchQuery->getStart());
        }

        if ($searchQuery->getLimit()) {
            $queryBuilder->setMaxResults($searchQuery->getLimit());
        }

        $ownershipSearchRows = $queryBuilder
            ->orderBy('owner_id', 'ASC')
            ->execute()
            ->fetchAllAssociative();

        return new OwnershipItemCollection(
            ...array_map(
                fn (array $ownershipSearchRow) => $this->createOwnershipSearchItem($ownershipSearchRow),
                $ownershipSearchRows
            )
        );
    }

    public function searchTotal(SearchQuery $searchQuery): int
    {
        $queryBuilder = $this->createSearchQueryBuilder($searchQuery)
            ->select('COUNT(*)');

        return (int) $queryBuilder
            ->execute()
            ->fetchOne();
    }

    private function createSearchQueryBuilder(SearchQuery $searchQuery): QueryBuilder
    {
        $queryBuilder = $this->connection->createQueryBuilder()
            ->from('ownership_search');

        /**
         * @var array<string, array<int, string>> $urlParameterToValues
         */
        $urlParameterToValues = [];

        foreach ($searchQuery->getParameters() as $parameter) {
            $urlParameter = $parameter->getUrlParameter();
            $urlParameterToValues[$urlParameter][] = $parameter->getValue();
        }

        foreach ($urlParameterToValues as $urlParameter => $values) {
            $column = self::URL_PARAMETER_TO_COLUMN[$urlParameter];

            if (in_array($urlParameter, self::URL_PARAMETER_WITH_MULTIPLE_VALUES)) {
                $queryBuilder = $queryBuilder
                    ->andWhere(
                        $queryBuilder->expr()->in($column, ":{$column}_values")
                    )
                    ->setParameter(":{$column}_values", $values, Connection::PARAM_STR_ARRAY);
                continue;
            }

            $queryBuilder = $queryBuilder
                ->andWhere($column . ' = :' . $column)
                ->setParameter($column, $values[0]);
        }

        return $queryBuilder;
    }

    private function createOwnershipSearchItem(array $ownershipSearchRow): OwnershipItem
    {
        $ownershipItem = new OwnershipItem(
            $ownershipSearchRow['id'],
            $ownershipSearchRow['item_id'],
            $ownershipSearchRow['item_type'],
            $ownershipSearchRow['owner_id'],
            $ownershipSearchRow['state']
        );

        if ($ownershipSearchRow['role_id']) {
            $ownershipItem = $ownershipItem->withRoleId(new Uuid($ownershipSearchRow['role_id']));
        }

        return $ownershipItem;
    }
}
