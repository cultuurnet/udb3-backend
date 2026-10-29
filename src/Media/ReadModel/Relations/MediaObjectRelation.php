<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media\ReadModel\Relations;

use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;

final class MediaObjectRelation
{
    private Uuid $mediaObjectId;
    private ItemType $itemType;
    private string $itemId;

    public function __construct(Uuid $mediaObjectId, ItemType $itemType, string $itemId)
    {
        $this->mediaObjectId = $mediaObjectId;
        $this->itemType = $itemType;
        $this->itemId = $itemId;
    }

    public function getMediaObjectId(): Uuid
    {
        return $this->mediaObjectId;
    }

    public function getItemType(): ItemType
    {
        return $this->itemType;
    }

    public function getItemId(): string
    {
        return $this->itemId;
    }
}
