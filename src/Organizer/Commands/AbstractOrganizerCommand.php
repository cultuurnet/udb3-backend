<?php

namespace CultuurNet\UDB3\Organizer\Commands;

abstract class AbstractOrganizerCommand
{
    /**
     * @var string
     */
    private $organizerId;

    /**
     * @param string $organizerId
     */
    public function __construct($organizerId)
    {
        $this->organizerId = $organizerId;
    }

    /**
     * @return string
     */
    public function getOrganizerId()
    {
        return $this->organizerId;
    }
}
