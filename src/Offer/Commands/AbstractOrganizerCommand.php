<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

abstract class AbstractOrganizerCommand extends AbstractCommand
{
    protected string $organizerId;

    /**
     * @param string $itemId
     * @param string $organizerId
     */
    public function __construct($itemId, $organizerId)
    {
        parent::__construct($itemId);
        $this->organizerId = $organizerId;
    }

    public function getOrganizerId(): string
    {
        return $this->organizerId;
    }
}
