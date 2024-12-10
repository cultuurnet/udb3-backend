<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Organizer;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

class OrganizerReference
{
    private UUID $organizerId;

    private function __construct(UUID $organizerId)
    {
        $this->organizerId = $organizerId;
    }

    public function getOrganizerId(): UUID
    {
        return $this->organizerId;
    }

    public static function createWithOrganizerId(UUID $organizerId): OrganizerReference
    {
        return new self($organizerId);
    }
}
