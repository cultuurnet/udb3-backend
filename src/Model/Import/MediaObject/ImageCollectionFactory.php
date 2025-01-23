<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\MediaObject;

use CultuurNet\UDB3\Media\ImageCollection;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Images;

interface ImageCollectionFactory
{
    public function fromImages(Images $images): ImageCollection;
}
