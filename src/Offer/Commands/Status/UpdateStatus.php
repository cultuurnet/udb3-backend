<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands\Status;

use CultuurNet\UDB3\Event\ValueObjects\Status;
use CultuurNet\UDB3\Offer\Commands\AuthorizableCommandInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;

final class UpdateStatus implements AuthorizableCommandInterface
{
    /**
     * @var string
     */
    private $offerId;

    /**
     * @var Status
     */
    private $status;

    public function __construct(string $offerId, Status $status)
    {
        $this->offerId = $offerId;
        $this->status = $status;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function getItemId(): string
    {
        return $this->offerId;
    }

    public function getPermission(): Permission
    {
        return  Permission::AANBOD_BEWERKEN();
    }
}
