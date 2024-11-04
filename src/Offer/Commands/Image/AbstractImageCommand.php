<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands\Image;

use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Offer\Commands\AbstractCommand;

abstract class AbstractImageCommand extends AbstractCommand
{
    protected Image $image;

    public function __construct(string $itemId, Image $image)
    {
        parent::__construct($itemId);
        $this->image = $image;
    }

    public function getImage(): Image
    {
        return $this->image;
    }
}
