<?php

namespace CultuurNet\UDB3\Model\ValueObject\MediaObject;

use CultuurNet\UDB3\Model\ValueObject\Collection\Collection;

class MediaObjectReferences extends Collection
{
    /**
     * @param MediaObjectReference[] ...$mediaObjectReferences
     */
    public function __construct(MediaObjectReference ...$mediaObjectReferences)
    {
        parent::__construct(...$mediaObjectReferences);
    }

    /**
     * @return MediaObjectReferences
     */
    public function getReferencesWithEmbeddedMediaObject()
    {
        return $this->filter(
            function (MediaObjectReference $reference) {
                return $reference->getEmbeddedMediaObject();
            }
        );
    }

    /**
     * @return MediaObjectReferences
     */
    public function getReferencesWithoutEmbeddedMediaObject()
    {
        return $this->filter(
            function (MediaObjectReference $reference) {
                return !$reference->getEmbeddedMediaObject();
            }
        );
    }
}
