<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands\Image;

use CultuurNet\UDB3\Media\ImageCollection;
use CultuurNet\UDB3\Offer\Commands\AbstractCommand;

class AbstractImportImages extends AbstractCommand
{
    private ImageCollection $imageCollection;

    /**
     * @param string $itemId
     */
    public function __construct($itemId, ImageCollection $imageCollection)
    {
        parent::__construct($itemId);
        $this->imageCollection = $imageCollection;
    }

    public function getImages(): ImageCollection
    {
        return $this->imageCollection;
    }
}
