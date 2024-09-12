<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\MediaObject;

use CultuurNet\UDB3\Media\ImageCollection;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\MediaObjectReferences;

interface ImageCollectionFactory
{
    public function fromMediaObjectReferences(MediaObjectReferences $mediaObjectReferences): ImageCollection;
}
