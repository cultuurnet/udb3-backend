<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\AuthorizableCommand;

final class RemoveImage implements AuthorizableCommand
{
    private string $organizerId;

    private Uuid $imageId;

    public function __construct(string $organizerId, Uuid $imageId)
    {
        $this->organizerId = $organizerId;
        $this->imageId = $imageId;
    }

    public function getImageId(): Uuid
    {
        return $this->imageId;
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
