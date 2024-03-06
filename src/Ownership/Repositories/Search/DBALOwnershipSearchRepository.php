<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\Repositories\Search;

use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItemCollection;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItemNotFound;
use Doctrine\DBAL\Connection;

final class DBALOwnershipSearchRepository implements OwnershipSearchRepository
{
    private Connection $connection;

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
        ]);
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

    public function getByItemIdAndOwnerId(string $itemId, string $ownerId): OwnershipItem
    {
        $ownershipSearchRow = $this->connection->createQueryBuilder()
            ->select('*')
            ->from('ownership_search')
            ->where('item_id = :item_id')
            ->andWhere('owner_id = :owner_id')
            ->setParameter('item_id', $itemId)
            ->setParameter('owner_id', $ownerId)
            ->execute()
            ->fetchAllAssociative();

        if (count($ownershipSearchRow) !== 1) {
            throw OwnershipItemNotFound::byItemIdAndOwnerId($itemId, $ownerId);
        }

        return $this->createOwnershipSearchItem($ownershipSearchRow[0]);
    }

    public function getByItemId(string $itemId): OwnershipItemCollection
    {
        $ownershipSearchRows = $this->connection->createQueryBuilder()
            ->select('*')
            ->from('ownership_search')
            ->where('item_id = :item_id')
            ->setParameter('item_id', $itemId)
            ->execute()
            ->fetchAllAssociative();

        return new OwnershipItemCollection(
            ...array_map(
                fn (array $ownershipSearchRow) => $this->createOwnershipSearchItem($ownershipSearchRow),
                $ownershipSearchRows
            )
        );
    }

    private function createOwnershipSearchItem(array $ownershipSearchRow): OwnershipItem
    {
        return new OwnershipItem(
            $ownershipSearchRow['id'],
            $ownershipSearchRow['item_id'],
            $ownershipSearchRow['item_type'],
            $ownershipSearchRow['owner_id']
        );
    }
}
