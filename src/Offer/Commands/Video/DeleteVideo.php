<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands\Video;

use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\AuthorizableCommand;

final class DeleteVideo implements AuthorizableCommand
{
    private string $offerId;

    private string $videoId;

    public function __construct(string $offerId, string $videoId)
    {
        $this->offerId = $offerId;
        $this->videoId = $videoId;
    }

    public function getVideoId(): string
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
