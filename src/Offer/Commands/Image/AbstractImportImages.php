<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands\Image;

use CultuurNet\UDB3\Media\ImageCollection;
use CultuurNet\UDB3\Offer\Commands\AbstractCommand;

class AbstractImportImages extends AbstractCommand
{
    private ImageCollection $imageCollection;

    public function __construct(string $itemId, ImageCollection $imageCollection)
    {
        parent::__construct($itemId);
        $this->imageCollection = $imageCollection;
    }

    public function getImages(): ImageCollection
    {
        return $this->imageCollection;
    }
}
