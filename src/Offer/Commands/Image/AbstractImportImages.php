<?php

namespace CultuurNet\UDB3\Offer\Commands\Image;

use CultuurNet\UDB3\Media\ImageCollection;
use CultuurNet\UDB3\Offer\Commands\AbstractCommand;

class AbstractImportImages extends AbstractCommand
{
    /**
     * @var ImageCollection
     */
    private $imageCollection;

    /**
     * @param string $itemId
     * @param ImageCollection $imageCollection
     */
    public function __construct($itemId, ImageCollection $imageCollection)
    {
        parent::__construct($itemId);
        $this->imageCollection = $imageCollection;
    }

    /**
     * @return ImageCollection
     */
    public function getImages()
    {
        return $this->imageCollection;
    }
}
