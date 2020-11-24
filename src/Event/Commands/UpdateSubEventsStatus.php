<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\Event\ValueObjects\EventStatus;
use CultuurNet\UDB3\Offer\Commands\AuthorizableCommandInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;

final class UpdateSubEventsStatus implements AuthorizableCommandInterface
{
    /**
     * @var string
     */
    private $eventId;

    /**
     * @var EventStatus[]
     */
    private $eventStatuses;

    public function __construct(string $eventId)
    {
        $this->eventId = $eventId;
    }

    public function withUpdatedStatus(int $timestampPosition, EventStatus $eventStatus): self
    {
        $c = clone $this;
        $c->eventStatuses[$timestampPosition] = $eventStatus;
        return $c;
    }

    public function getItemId(): string
    {
        return $this->eventId;
    }

    public function getEventStatuses(): array
    {
        return $this->eventStatuses;
    }

    public function getPermission(): Permission
    {
        return Permission::AANBOD_BEWERKEN();
    }
}
