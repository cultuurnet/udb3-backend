<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands\Video;

use CultuurNet\UDB3\Model\ValueObject\MediaObject\VideoCollection;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\AuthorizableCommand;

final class ImportVideos implements AuthorizableCommand
{
    private string $offerId;

    private VideoCollection $videos;

    public function __construct(string $offerId, VideoCollection $videos)
    {
        $this->offerId = $offerId;
        $this->videos = $videos;
    }

    public function getVideos(): VideoCollection
    {
        return $this->videos;
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
