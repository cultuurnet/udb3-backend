<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media\ReadModel\Relations;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;

interface MediaObjectRelationsRepository
{
    public function add(MediaObjectRelation $relation): void;

    public function remove(Uuid $mediaObjectId, string $itemId): void;

    public function removeItem(string $itemId): void;

    /**
     * @return MediaObjectRelation[]
     */
    public function getByMediaObject(Uuid $mediaObjectId): array;

    /**
     * @return MediaObjectRelation[]
     */
    public function getByItem(string $itemId): array;
}
