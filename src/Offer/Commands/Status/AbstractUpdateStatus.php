<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands\Status;

use CultuurNet\UDB3\Event\ValueObjects\Status;
use CultuurNet\UDB3\Offer\Commands\AuthorizableCommandInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;

abstract class AbstractUpdateStatus implements AuthorizableCommandInterface
{
    /**
     * @var string
     */
    private $eventId;

    /**
     * @var Status
     */
    private $status;

    public function __construct(string $eventId, Status $status)
    {
        $this->eventId = $eventId;
        $this->status = $status;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function getItemId(): string
    {
        return $this->eventId;
    }

    public function getPermission(): Permission
    {
        return  Permission::AANBOD_BEWERKEN();
    }
}
