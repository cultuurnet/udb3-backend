<?php

namespace CultuurNet\UDB3\Offer\Commands;

abstract class AbstractOrganizerCommand extends AbstractCommand
{
    /**
     * OrganizerId to be set
     * @var string
     */
    protected $organizerId;

    /**
     * @param string $itemId
     * @param string $organizerId
     */
    public function __construct($itemId, $organizerId)
    {
        parent::__construct($itemId);
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
