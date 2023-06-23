<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands\Image;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Offer\Commands\AbstractCommand;

abstract class AbstractAddImage extends AbstractCommand
{
    protected UUID $imageId;

    public function __construct(string $itemId, UUID $imageId)
    {
        parent::__construct($itemId);
        $this->imageId = $imageId;
    }

    public function getImageId(): UUID
    {
        return $this->imageId;
    }
}
