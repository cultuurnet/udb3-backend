<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Identity;

use CultuurNet\UDB3\Model\ValueObject\Web\Url;

final class ItemIdentifier
{
    private Url $url;

    private string $id;

    private ItemType $itemType;

    public function __construct(Url $url, string $id, ItemType $itemType)
    {
        $this->url = $url;
        $this->id = $id;
        $this->itemType = $itemType;
    }

    public function getUrl(): Url
    {
        return $this->url;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getItemType(): ItemType
    {
        return $this->itemType;
    }
}
