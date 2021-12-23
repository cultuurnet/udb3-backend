<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\AuthorizableCommand;

final class UpdateOrganizer implements AuthorizableCommand
{
    private string $organizerId;

    private ?UUID $mainImageId = null;

    public function __construct(string $organizerId)
    {
        $this->organizerId = $organizerId;
    }

    public function getMainImageId(): ?UUID
    {
        return $this->mainImageId;
    }

    public function withMainImageId(UUID $mainImageId): UpdateOrganizer
    {
        $clone = clone $this;
        $clone->mainImageId = $mainImageId;
        return $clone;
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
