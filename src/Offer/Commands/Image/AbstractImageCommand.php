<?php

namespace CultuurNet\UDB3\Offer\Commands\Image;

use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Offer\Commands\AbstractCommand;

abstract class AbstractImageCommand extends AbstractCommand
{
    /**
     * @var Image
     */
    protected $image;

    /**
     * @param $itemId
     *  The id of the item that is targeted by the command.
     *
     * @param Image $image
     *  The image that is used in the command.
     */
    public function __construct($itemId, Image $image)
    {
        parent::__construct($itemId);
        $this->image = $image;
    }

    /**
     * @return Image
     */
    public function getImage()
    {
        return $this->image;
    }
}
