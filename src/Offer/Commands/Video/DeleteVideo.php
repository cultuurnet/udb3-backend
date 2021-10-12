<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands\Video;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\AuthorizableCommand;

final class DeleteVideo implements AuthorizableCommand
{
    private string $offerId;

    private UUID $videoId;

    public function __construct(string $offerId, UUID $videoId)
    {
        $this->offerId = $offerId;
        $this->videoId = $videoId;
    }

    public function getVideoId(): UUID
    {
        return $this->videoId;
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
