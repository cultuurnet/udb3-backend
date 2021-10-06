<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands\Video;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Video;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\AuthorizableCommand;

final class AddVideo implements AuthorizableCommand
{
    private UUID $offerId;

    private Video $video;

    public function __construct(UUID $offerId, Video $video)
    {
        $this->offerId = $offerId;
        $this->video = $video;
    }

    public function getOfferId(): UUID
    {
        return $this->offerId;
    }

    public function getVideo(): Video
    {
        return $this->video;
    }

    public function getItemId(): string
    {
        return $this->offerId->toString();
    }

    public function getPermission(): Permission
    {
        return Permission::AANBOD_BEWERKEN();
    }
}
