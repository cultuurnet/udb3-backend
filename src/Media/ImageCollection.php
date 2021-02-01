<?php

namespace CultuurNet\UDB3\Media;

use ArrayIterator;
use TwoDotsTwice\Collection\AbstractCollection;
use TwoDotsTwice\Collection\CollectionInterface;
use ValueObjects\Identity\UUID;

class ImageCollection extends AbstractCollection implements CollectionInterface
{
    /**
     * @var Image|null
     */
    protected $mainImage;

    protected function getValidObjectType()
    {
        return Image::class;
    }

    /**
     * @param Image $image
     * @return ImageCollection
     */
    public function withMain(Image $image)
    {
        $collection = $this->contains($image) ? $this : $this->with($image);

        $copy = clone $collection;
        $copy->mainImage = $image;
        return $copy;
    }

    /**
     * @return Image|null
     */
    public function getMain()
    {
        if (0 === $this->length()) {
            return null;
        }

        if ($this->mainImage) {
            return $this->mainImage;
        } else {
            /** @var ArrayIterator $iterator */
            $iterator = $this->getIterator();
            $iterator->rewind();

            return $iterator->current();
        }
    }

    /**
     * @param UUID $uuid
     * @return Image|null
     */
    public function findImageByUUID(UUID $uuid)
    {
        /** @var Image $image */
        foreach ($this->items as $image) {
            if ($image->getMediaObjectId()->sameValueAs($uuid)) {
                return $image;
            }
        }

        return null;
    }
}
