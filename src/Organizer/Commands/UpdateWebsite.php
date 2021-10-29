<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\AuthorizableCommand;
use ValueObjects\Web\Url;

final class UpdateWebsite implements AuthorizableCommand
{
    private string $organizerId;

    private Url $website;

    public function __construct(
        string $organizerId,
        Url $website
    ) {
        $this->organizerId = $organizerId;
        $this->website = $website;
    }

    public function getWebsite(): Url
    {
        return $this->website;
    }

    public function getItemId(): string
    {
        return $this->organizerId;
    }

    public function getPermission(): Permission
    {
        return Permission::ORGANISATIES_BEWERKEN();
    }
}
