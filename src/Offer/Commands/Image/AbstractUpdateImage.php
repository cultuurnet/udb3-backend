<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands\Image;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Offer\Commands\AbstractCommand;
use CultuurNet\UDB3\StringLiteral;

abstract class AbstractUpdateImage extends AbstractCommand
{
    protected UUID $mediaObjectId;

    protected string $description;

    protected CopyrightHolder $copyrightHolder;

    public function __construct(
        string $itemId,
        UUID $mediaObjectId,
        string $description,
        CopyrightHolder $copyrightHolder
    ) {
        parent::__construct($itemId);
        $this->mediaObjectId = $mediaObjectId;
        $this->description = $description;
        $this->copyrightHolder = $copyrightHolder;
    }

    public function getMediaObjectId(): UUID
    {
        return $this->mediaObjectId;
    }

    public function getDescription(): StringLiteral
    {
        return new StringLiteral($this->description);
    }

    public function getCopyrightHolder(): CopyrightHolder
    {
        return $this->copyrightHolder;
    }
}
