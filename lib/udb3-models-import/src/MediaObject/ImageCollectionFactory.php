<?php

namespace CultuurNet\UDB3\Model\Import\MediaObject;

use CultuurNet\UDB3\Media\ImageCollection;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\MediaObjectReferences;

interface ImageCollectionFactory
{
    /**
     * @param MediaObjectReferences $mediaObjectReferences
     * @return ImageCollection
     */
    public function fromMediaObjectReferences(MediaObjectReferences $mediaObjectReferences);
}
