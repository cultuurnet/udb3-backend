<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

use Broadway\Serializer\Serializable;

final class ImageRemoved implements Serializable
{
    private string $organizerId;

    private string $imageId;

    public function __construct(string $organizerId, string $imageId)
    {
        $this->organizerId = $organizerId;
        $this->imageId = $imageId;
    }

    public function getOrganizerId(): string
    {
        return $this->organizerId;
    }

    public function getImageId(): string
    {
        return $this->imageId;
    }

    public function serialize(): array
    {
        return [
            'organizerId' => $this->organizerId,
            'imageId' => $this->imageId,
        ];
    }

    public static function deserialize(array $data): ImageRemoved
    {
        return new ImageRemoved(
            $data['organizerId'],
            $data['imageId']
        );
    }
}
