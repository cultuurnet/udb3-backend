<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Commands;

abstract class AbstractOrganizerCommand
{
    /**
     * @var string
     */
    private $organizerId;

    public function __construct(string $organizerId)
    {
        $this->organizerId = $organizerId;
    }

    public function getOrganizerId(): string
    {
        return $this->organizerId;
    }
}
