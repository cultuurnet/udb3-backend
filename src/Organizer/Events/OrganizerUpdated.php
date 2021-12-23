<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

use Broadway\Serializer\Serializable;

final class OrganizerUpdated implements Serializable
{
    private string $organizerId;

    private ?string $mainImageId = null;

    public function __construct(string $organizerId)
    {
        $this->organizerId = $organizerId;
    }

    public function getOrganizerId(): string
    {
        return $this->organizerId;
    }

    public function withMainImageId(string $mainImageId): OrganizerUpdated
    {
        $clone = clone $this;
        $clone->mainImageId = $mainImageId;
        return $clone;
    }

    public function getMainImageId(): ?string
    {
        return $this->mainImageId;
    }

    public function serialize(): array
    {
        $organizerUpdatedAsArray =  [
            'organizerId' => $this->organizerId,
            'mainImageId' => $this->getMainImageId(),
        ];

        return array_filter($organizerUpdatedAsArray);
    }

    public static function deserialize(array $data): OrganizerUpdated
    {
        $organizerUpdated = new OrganizerUpdated(
            $data['organizerId'],
        );

        if (isset($data['mainImageId'])) {
            $organizerUpdated = $organizerUpdated->withMainImageId($data['mainImageId']);
        }

        return $organizerUpdated;
    }
}
