<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media\ReadModel\Relations;

use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

final class DBALMediaObjectRelationsRepository implements MediaObjectRelationsRepository
{
    private const TABLE_NAME = 'media_object_relations';

    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function add(string $mediaObjectId, ItemType $itemType, string $itemId): void
    {
        try {
            $this->connection->insert(self::TABLE_NAME, [
                'media_object_id' => $mediaObjectId,
                'item_type' => $itemType->toString(),
                'item_id' => $itemId,
            ]);
        } catch (UniqueConstraintViolationException $exception) {
            // The relation already exists.
        }
    }

    public function remove(string $mediaObjectId, string $itemId): void
    {
        $this->connection->delete(self::TABLE_NAME, [
            'media_object_id' => $mediaObjectId,
            'item_id' => $itemId,
        ]);
    }

    public function removeAllForItem(string $itemId): void
    {
        $this->connection->delete(self::TABLE_NAME, ['item_id' => $itemId]);
    }

    public function getRelations(string $mediaObjectId): array
    {
        $rows = $this->connection->createQueryBuilder()
            ->select('item_type', 'item_id')
            ->from(self::TABLE_NAME)
            ->where('media_object_id = :mediaObjectId')
            ->setParameter('mediaObjectId', $mediaObjectId)
            ->orderBy('item_id')
            ->execute()
            ->fetchAllAssociative();

        return array_map(
            fn (array $row) => new MediaObjectRelation(new ItemType($row['item_type']), $row['item_id']),
            $rows
        );
    }

    public function getMediaObjectIds(string $itemId): array
    {
        return $this->connection->createQueryBuilder()
            ->select('media_object_id')
            ->from(self::TABLE_NAME)
            ->where('item_id = :itemId')
            ->setParameter('itemId', $itemId)
            ->orderBy('media_object_id')
            ->execute()
            ->fetchFirstColumn();
    }
}
