<?php

namespace CultuurNet\UDB3\Offer\Commands\Image;

use CultuurNet\UDB3\Offer\Commands\AbstractCommand;
use ValueObjects\Identity\UUID;

abstract class AbstractAddImage extends AbstractCommand
{
    /**
     * @var UUID
     */
    protected $imageId;

    public function __construct($itemId, UUID $imageId)
    {
        parent::__construct($itemId);
        $this->imageId = $imageId;
    }

    /**
     * @return UUID
     */
    public function getImageId()
    {
        return $this->imageId;
    }
}
