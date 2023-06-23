<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

use ArrayIterator;
use CultuurNet\UDB3\Collection\AbstractCollection;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

class ImageCollection extends AbstractCollection
{
    protected ?Image $mainImage = null;

    protected function getValidObjectType(): string
    {
        return Image::class;
    }

    public function withMain(Image $image): ImageCollection
    {
        $collection = $this->contains($image) ? $this : $this->with($image);

        $copy = clone $collection;
        $copy->mainImage = $image;
        return $copy;
    }

    public function getMain(): ?Image
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

    public function findImageByUUID(UUID $uuid): ?Image
    {
        /** @var Image $image */
        foreach ($this->items as $image) {
            if ($image->getMediaObjectId()->sameAs($uuid)) {
                return $image;
            }
        }

        return null;
    }
}
