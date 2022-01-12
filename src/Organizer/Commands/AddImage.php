<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Model\ValueObject\MediaObject\Image;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\AuthorizableCommand;

final class AddImage implements AuthorizableCommand
{
    private string $organizerId;

    private Image $image;

    public function __construct(string $organizerId, Image $image)
    {
        $this->organizerId = $organizerId;
        $this->image = $image;
    }

    public function getImage(): Image
    {
        return $this->image;
    }

    public function getItemId(): string
    {
        return $this->organizerId;
    }

    public function getPermission(): Permission
    {
        return Permission::organisatiesBewerken();
    }
}
