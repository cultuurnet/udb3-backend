<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands\Image;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Offer\Commands\AbstractCommand;

abstract class AbstractUpdateImage extends AbstractCommand
{
    protected Uuid $mediaObjectId;

    protected string $description;

    protected CopyrightHolder $copyrightHolder;

    public function __construct(
        string $itemId,
        Uuid $mediaObjectId,
        string $description,
        CopyrightHolder $copyrightHolder
    ) {
        parent::__construct($itemId);
        $this->mediaObjectId = $mediaObjectId;
        $this->description = $description;
        $this->copyrightHolder = $copyrightHolder;
    }

    public function getMediaObjectId(): Uuid
    {
        return $this->mediaObjectId;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getCopyrightHolder(): CopyrightHolder
    {
        return $this->copyrightHolder;
    }
}
