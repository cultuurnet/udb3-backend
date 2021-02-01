<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Role\ValueObjects\Permission;

final class ChangeOwner implements AuthorizableCommandInterface
{
    /**
     * @var string
     */
    private $offerId;

    /**
     * @var string
     */
    private $newOwnerId;

    public function __construct(string $offerId, string $newOwnerId)
    {
        $this->offerId = $offerId;
        $this->newOwnerId = $newOwnerId;
    }

    public function getOfferId(): string
    {
        return $this->offerId;
    }

    public function getNewOwnerId(): string
    {
        return $this->newOwnerId;
    }

    public function getItemId(): string
    {
        return $this->offerId;
    }

    public function getPermission(): Permission
    {
        return Permission::AANBOD_BEWERKEN();
    }
}
