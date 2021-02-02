<?php

namespace CultuurNet\UDB3\Model\Organizer;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

class OrganizerReference
{
    /**
     * @var UUID
     */
    private $organizerId;

    /**
     * @var Organizer|null
     */
    private $embeddedOrganizer;

    /**
     * @param UUID $organizerId
     * @param Organizer|null $embeddedOrganizer
     */
    private function __construct(UUID $organizerId, Organizer $embeddedOrganizer = null)
    {
        if ($embeddedOrganizer) {
            $organizerId = $embeddedOrganizer->getId();
        }

        $this->organizerId = $organizerId;
        $this->embeddedOrganizer = $embeddedOrganizer;
    }

    /**
     * @return UUID
     */
    public function getOrganizerId()
    {
        return $this->organizerId;
    }

    /**
     * @return Organizer|null
     */
    public function getEmbeddedOrganizer()
    {
        return $this->embeddedOrganizer;
    }

    /**
     * @param UUID $organizerId
     * @return OrganizerReference
     */
    public static function createWithOrganizerId(UUID $organizerId)
    {
        return new self($organizerId);
    }

    /**
     * @param Organizer $organizer
     * @return OrganizerReference
     */
    public static function createWithEmbeddedOrganizer(Organizer $organizer)
    {
        return new self($organizer->getId(), $organizer);
    }
}
