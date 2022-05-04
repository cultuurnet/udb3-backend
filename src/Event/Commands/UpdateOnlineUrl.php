<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\AuthorizableCommand;

final class UpdateOnlineUrl implements AuthorizableCommand
{
    private string $eventId;

    private Url $onlineUrl;

    public function __construct(string $eventId, Url $onlineUrl)
    {
        $this->eventId = $eventId;
        $this->onlineUrl = $onlineUrl;
    }

    public function getItemId(): string
    {
        return $this->eventId;
    }

    public function getOnlineUrl(): Url
    {
        return $this->onlineUrl;
    }

    public function getPermission(): Permission
    {
        return Permission::aanbodBewerken();
    }
}
