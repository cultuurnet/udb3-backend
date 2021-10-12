<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Events;

use Broadway\Serializer\Serializable;

class AbstractVideoDeleted implements Serializable
{
    private string $itemId;

    private string $videoId;

    final public function __construct(string $itemId, string $videoId)
    {
        $this->itemId = $itemId;
        $this->videoId = $videoId;
    }

    public function getItemId(): string
    {
        return $this->itemId;
    }

    public function getVideoId(): string
    {
        return $this->videoId;
    }

    public static function deserialize(array $data): AbstractVideoDeleted
    {
        return new static(
            $data['item_id'],
            $data['video_id']
        );
    }

    public function serialize(): array
    {
        return [
            'item_id' => $this->itemId,
            'video_id' => $this->videoId,
        ];
    }
}
