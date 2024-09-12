<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Media;

use stdClass;

class MediaFinder
{
    private MediaSpecificationInterface $specification;

    /**
     * MediaFinder constructor.
     *
     */
    public function __construct(MediaSpecificationInterface $specification)
    {
        $this->specification = $specification;
    }

    /**
     * @param stdClass[] $media
     *  A list of media objects
     *
     * @return stdClass|null
     */
    public function find(array $media)
    {
        $specification = $this->specification;
        return array_reduce(
            $media,
            function ($matchingMedia, $mediaObject) use ($specification) {
                if ($matchingMedia) {
                    return $matchingMedia;
                }

                return $specification->matches($mediaObject) ? $mediaObject : null;
            }
        );
    }
}
