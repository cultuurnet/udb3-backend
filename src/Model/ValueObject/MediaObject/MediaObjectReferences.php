<?php

declare(strict_types=1);

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

    public function getReferencesWithEmbeddedMediaObject(): MediaObjectReferences
    {
        return $this->filter(
            function (MediaObjectReference $reference) {
                return $reference->getEmbeddedMediaObject();
            }
        );
    }

    public function getReferencesWithoutEmbeddedMediaObject(): MediaObjectReferences
    {
        return $this->filter(
            function (MediaObjectReference $reference) {
                return !$reference->getEmbeddedMediaObject();
            }
        );
    }

    public function toImages() : Images
    {
        $images = [];
        foreach ($this->toArray() as $mediaObjectReference) {
            $images[] = $mediaObjectReference->toImage();
        }
        return new Images(... $images);
    }
}
