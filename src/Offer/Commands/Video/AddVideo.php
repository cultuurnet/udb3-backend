<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands\Video;

use CultuurNet\UDB3\Model\ValueObject\MediaObject\Video;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\AuthorizableCommand;

final class AddVideo implements AuthorizableCommand
{
    private string $offerId;

    private Video $video;

    public function __construct(string $offerId, Video $video)
    {
        $this->offerId = $offerId;
        $this->video = $video;
    }

    public function getVideo(): Video
    {
        return $this->video;
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
