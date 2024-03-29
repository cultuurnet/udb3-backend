<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\Model\ValueObject\Price\Tariffs;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\AuthorizableCommand;

final class UpdateUiTPASPrices implements AuthorizableCommand
{
    private string $eventId;

    private Tariffs $tariffs;

    public function __construct(string $eventId, Tariffs $tariffs)
    {
        $this->eventId = $eventId;
        $this->tariffs = $tariffs;
    }

    public function getItemId(): string
    {
        return $this->eventId;
    }

    public function getTariffs(): Tariffs
    {
        return $this->tariffs;
    }

    public function getPermission(): Permission
    {
        return Permission::aanbodBewerken();
    }
}
