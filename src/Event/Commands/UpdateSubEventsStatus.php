<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\Event\ValueObjects\Status;
use CultuurNet\UDB3\Offer\Commands\AuthorizableCommandInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;

final class UpdateSubEventsStatus implements AuthorizableCommandInterface
{
    /**
     * @var string
     */
    private $eventId;

    /**
     * @var Status[]
     */
    private $statuses;

    public function __construct(string $eventId)
    {
        $this->eventId = $eventId;
    }

    public function withUpdatedStatus(int $timestampPosition, Status $status): self
    {
        $c = clone $this;
        $c->statuses[$timestampPosition] = $status;
        return $c;
    }

    public function getItemId(): string
    {
        return $this->eventId;
    }

    public function getStatuses(): array
    {
        return $this->statuses;
    }

    public function getPermission(): Permission
    {
        return Permission::AANBOD_BEWERKEN();
    }
}
