<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Organizer;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;

class OrganizerReference
{
    private Uuid $organizerId;

    private function __construct(Uuid $organizerId)
    {
        $this->organizerId = $organizerId;
    }

    public function getOrganizerId(): Uuid
    {
        return $this->organizerId;
    }

    public static function createWithOrganizerId(Uuid $organizerId): OrganizerReference
    {
        return new self($organizerId);
    }
}
