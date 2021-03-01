<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

use Broadway\Serializer\Serializable;

abstract class OrganizerEvent implements Serializable
{
    /**
     * @var string
     */
    protected $organizerId;

    public function __construct(string $organizerId)
    {
        $this->organizerId = $organizerId;
    }

    public function getOrganizerId(): string
    {
        return $this->organizerId;
    }

    public function serialize(): array
    {
        return [
          'organizer_id' => $this->organizerId,
        ];
    }
}
