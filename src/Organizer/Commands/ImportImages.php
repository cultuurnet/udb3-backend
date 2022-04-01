<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Model\ValueObject\MediaObject\Images;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\AuthorizableCommand;

final class ImportImages implements AuthorizableCommand
{
    private string $organizerId;
    private Images $images;

    public function __construct(string $organizerId, Images $images)
    {
        $this->organizerId = $organizerId;
        $this->images = $images;
    }

    public function getItemId(): string
    {
        return $this->organizerId;
    }

    public function getImages(): Images
    {
        return $this->images;
    }

    public function getPermission(): Permission
    {
        return Permission::organisatiesBewerken();
    }
}
