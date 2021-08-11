<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\Event\ValueObjects\SubEventUpdate;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\AuthorizableCommand;
use InvalidArgumentException;

class UpdateSubEvents implements AuthorizableCommand
{
    private string $eventId;

    /**
     * @var SubEventUpdate[]
     */
    private array $subEventUpdates;

    public function __construct(string $eventId, SubEventUpdate ...$updateSubEvents)
    {
        if (empty($updateSubEvents)) {
            throw new InvalidArgumentException('At least one UpdateSubEvent is required');
        }

        $this->eventId = $eventId;
        $this->subEventUpdates = $updateSubEvents;
    }

    public function getItemId(): string
    {
        return $this->eventId;
    }

    public function getPermission(): Permission
    {
        return  Permission::AANBOD_BEWERKEN();
    }

    public function getUpdates(): array
    {
        return $this->subEventUpdates;
    }
}
