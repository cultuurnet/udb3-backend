<?php

namespace CultuurNet\UDB3\Offer\Commands\Image;

use CultuurNet\UDB3\Offer\Commands\AbstractCommand;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

abstract class AbstractUpdateImage extends AbstractCommand
{
    /**
     * The id of the media object that the new information applies to.
     * @var UUID
     */
    protected $mediaObjectId;

    /**
     * @var StringLiteral
     */
    protected $description;

    /**
     * @var StringLiteral
     */
    protected $copyrightHolder;

    /**
     * @param $itemId
     * @param UUID $mediaObjectId
     * @param StringLiteral $description
     * @param StringLiteral $copyrightHolder
     */
    public function __construct(
        $itemId,
        UUID $mediaObjectId,
        StringLiteral $description,
        StringLiteral $copyrightHolder
    ) {
        parent::__construct($itemId);
        $this->mediaObjectId = $mediaObjectId;
        $this->description = $description;
        $this->copyrightHolder = $copyrightHolder;
    }

    /**
     * @return UUID
     */
    public function getMediaObjectId()
    {
        return $this->mediaObjectId;
    }

    /**
     * @return StringLiteral
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return StringLiteral
     */
    public function getCopyrightHolder()
    {
        return $this->copyrightHolder;
    }
}
