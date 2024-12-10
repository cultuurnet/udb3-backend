<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands\Image;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Offer\Commands\AbstractCommand;

abstract class AbstractAddImage extends AbstractCommand
{
    protected Uuid $imageId;

    public function __construct(string $itemId, Uuid $imageId)
    {
        parent::__construct($itemId);
        $this->imageId = $imageId;
    }

    public function getImageId(): Uuid
    {
        return $this->imageId;
    }
}
