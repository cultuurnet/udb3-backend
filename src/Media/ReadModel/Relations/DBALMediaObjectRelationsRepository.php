<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media\ReadModel\Relations;

use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

final class DBALMediaObjectRelationsRepository implements MediaObjectRelationsRepository
{
    private const TABLE = 'media_object_relations';

    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function add(MediaObjectRelation $relation): void
    {
        try {
            $this->connection->insert(
                self::TABLE,
                [
                    'media_object_id' => $relation->getMediaObjectId()->toString(),
                    'item_type' => $relation->getItemType()->toString(),
                    'item_id' => $relation->getItemId(),
                ]
            );
        } catch (UniqueConstraintViolationException) {
            // The media object is already linked to this item.
        }
    }

    public function remove(Uuid $mediaObjectId, string $itemId): void
    {
        $this->connection->delete(
            self::TABLE,
            [
                'media_object_id' => $mediaObjectId->toString(),
                'item_id' => $itemId,
            ]
        );
    }

    public function removeItem(string $itemId): void
    {
        $this->connection->delete(self::TABLE, ['item_id' => $itemId]);
    }

    public function getByMediaObject(Uuid $mediaObjectId): array
    {
        $rows = $this->connection->createQueryBuilder()
            ->select('media_object_id', 'item_type', 'item_id')
            ->from(self::TABLE)
            ->where('media_object_id = :mediaObjectId')
            ->setParameter(':mediaObjectId', $mediaObjectId->toString())
            ->orderBy('item_id')
            ->execute()
            ->fetchAllAssociative();

        return array_map(fn (array $row) => $this->createRelation($row), $rows);
    }

    public function getByItem(string $itemId): array
    {
        $rows = $this->connection->createQueryBuilder()
            ->select('media_object_id', 'item_type', 'item_id')
            ->from(self::TABLE)
            ->where('item_id = :itemId')
            ->setParameter(':itemId', $itemId)
            ->orderBy('media_object_id')
            ->execute()
            ->fetchAllAssociative();

        return array_map(fn (array $row) => $this->createRelation($row), $rows);
    }

    private function createRelation(array $row): MediaObjectRelation
    {
        return new MediaObjectRelation(
            new Uuid($row['media_object_id']),
            new ItemType($row['item_type']),
            $row['item_id']
        );
    }
}
