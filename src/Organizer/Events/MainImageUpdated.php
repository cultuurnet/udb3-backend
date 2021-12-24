<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

use Broadway\Serializer\Serializable;

final class MainImageUpdated implements Serializable
{
    private string $organizerId;

    private string $mainImageId;

    public function __construct(string $organizerId, string $mainImageId)
    {
        $this->organizerId = $organizerId;
        $this->mainImageId = $mainImageId;
    }

    public function getOrganizerId(): string
    {
        return $this->organizerId;
    }

    public function getMainImageId(): string
    {
        return $this->mainImageId;
    }

    public function serialize(): array
    {
        return  [
            'organizerId' => $this->organizerId,
            'mainImageId' => $this->getMainImageId(),
        ];
    }

    public static function deserialize(array $data): MainImageUpdated
    {
        return new MainImageUpdated(
            $data['organizerId'],
            $data['mainImageId']
        );
    }
}
