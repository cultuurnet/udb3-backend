<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media\ReadModel\Relations;

use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;

final class MediaObjectRelation
{
    private ItemType $itemType;

    private string $itemId;

    public function __construct(ItemType $itemType, string $itemId)
    {
        $this->itemType = $itemType;
        $this->itemId = $itemId;
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
