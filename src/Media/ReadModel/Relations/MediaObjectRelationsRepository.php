<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media\ReadModel\Relations;

use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;

interface MediaObjectRelationsRepository
{
    public function add(string $mediaObjectId, ItemType $itemType, string $itemId): void;

    public function remove(string $mediaObjectId, string $itemId): void;

    public function removeAllForItem(string $itemId): void;

    /**
     * @return MediaObjectRelation[]
     */
    public function getRelations(string $mediaObjectId): array;

    /**
     * @return string[]
     */
    public function getMediaObjectIds(string $itemId): array;
}
