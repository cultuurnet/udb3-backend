<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Events;

use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

class AbstractVideoDeleted implements Serializable
{
    private UUID $itemId;

    private UUID $videoId;

    final public function __construct(UUID $itemId, UUID $videoId)
    {
        $this->itemId = $itemId;
        $this->videoId = $videoId;
    }

    public function getItemId(): UUID
    {
        return $this->itemId;
    }

    public function getVideoId(): UUID
    {
        return $this->videoId;
    }

    public static function deserialize(array $data): AbstractVideoDeleted
    {
        return new static(
            new UUID($data['item_id']),
            new UUID($data['video_id'])
        );
    }

    public function serialize(): array
    {
        return [
            'item_id' => $this->itemId->toString(),
            'video_id' => $this->videoId->toString(),
        ];
    }
}
