<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

abstract class AbstractOrganizerCommand extends AbstractCommand
{
    protected string $organizerId;

    public function __construct(string $itemId, string $organizerId)
    {
        parent::__construct($itemId);
        $this->organizerId = $organizerId;
    }

    public function getOrganizerId(): string
    {
        return $this->organizerId;
    }
}
