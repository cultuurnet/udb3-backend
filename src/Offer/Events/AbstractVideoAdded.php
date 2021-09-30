<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Events;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Video;

class AbstractVideoAdded
{
    private UUID $itemId;

    private Video $video;

    public function __construct(UUID $itemId, Video $video)
    {
        $this->itemId = $itemId;
        $this->video = $video;
    }

    public function getItemId(): UUID
    {
        return $this->itemId;
    }

    public function getVideo(): Video
    {
        return $this->video;
    }
}
