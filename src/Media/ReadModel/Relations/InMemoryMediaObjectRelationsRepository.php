<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media\ReadModel\Relations;

use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;

/**
 * In-memory implementation of MediaObjectRelationsRepository, mostly useful in tests.
 */
final class InMemoryMediaObjectRelationsRepository implements MediaObjectRelationsRepository
{
    /**
     * @var array<string, array<string, ItemType>> Item types keyed by media object id and item id.
     */
    private array $relations = [];

    public function add(string $mediaObjectId, ItemType $itemType, string $itemId): void
    {
        $this->relations[$mediaObjectId][$itemId] = $itemType;
    }

    public function remove(string $mediaObjectId, string $itemId): void
    {
        unset($this->relations[$mediaObjectId][$itemId]);
    }

    public function removeAllForItem(string $itemId): void
    {
        foreach (array_keys($this->relations) as $mediaObjectId) {
            unset($this->relations[$mediaObjectId][$itemId]);
        }
    }

    public function getRelations(string $mediaObjectId): array
    {
        $relations = $this->relations[$mediaObjectId] ?? [];
        ksort($relations);

        $result = [];
        foreach ($relations as $itemId => $itemType) {
            $result[] = new MediaObjectRelation($itemType, (string) $itemId);
        }
        return $result;
    }

    public function getMediaObjectIds(string $itemId): array
    {
        $mediaObjectIds = [];
        foreach ($this->relations as $mediaObjectId => $items) {
            if (isset($items[$itemId])) {
                $mediaObjectIds[] = (string) $mediaObjectId;
            }
        }
        sort($mediaObjectIds);
        return $mediaObjectIds;
    }
}
