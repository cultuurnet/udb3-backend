<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Organizer;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

class OrganizerReference
{
    private UUID $organizerId;

    private ?Organizer $embeddedOrganizer;

    private function __construct(UUID $organizerId, Organizer $embeddedOrganizer = null)
    {
        if ($embeddedOrganizer) {
            $organizerId = $embeddedOrganizer->getId();
        }

        $this->organizerId = $organizerId;
        $this->embeddedOrganizer = $embeddedOrganizer;
    }

    public function getOrganizerId(): UUID
    {
        return $this->organizerId;
    }

    public function getEmbeddedOrganizer(): ?Organizer
    {
        return $this->embeddedOrganizer;
    }

    public static function createWithOrganizerId(UUID $organizerId): OrganizerReference
    {
        return new self($organizerId);
    }

    public static function createWithEmbeddedOrganizer(Organizer $organizer): OrganizerReference
    {
        return new self($organizer->getId(), $organizer);
    }
}
