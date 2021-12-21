<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

use Broadway\Serializer\Serializable;

final class ImageUpdated implements Serializable
{
    private string $organizerId;

    private string $imageId;

    private string $description;

    private string $copyrightHolder;

    public function __construct(
        string $organizerId,
        string $imageId,
        string $description,
        string $copyrightHolder
    ) {
        $this->organizerId = $organizerId;
        $this->imageId = $imageId;
        $this->description = $description;
        $this->copyrightHolder = $copyrightHolder;
    }

    public function getOrganizerId(): string
    {
        return $this->organizerId;
    }

    public function getImageId(): string
    {
        return $this->imageId;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getCopyrightHolder(): string
    {
        return $this->copyrightHolder;
    }

    public function serialize(): array
    {
        return [
            'organizerId' => $this->organizerId,
            'imageId' => $this->imageId,
            'description' => $this->description,
            'copyrightHolder' => $this->copyrightHolder,
        ];
    }

    public static function deserialize(array $data): ImageUpdated
    {
        return new ImageUpdated(
            $data['organizerId'],
            $data['imageId'],
            $data['description'],
            $data['copyrightHolder'],
        );
    }
}
