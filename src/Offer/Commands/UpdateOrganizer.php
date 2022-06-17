<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

final class UpdateOrganizer extends AbstractCommand
{
    private string $organizerId;

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
