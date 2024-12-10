<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

use CultuurNet\UDB3\Model\ValueObject\Collection\Collection;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

class ImageCollection extends Collection
{
    private ?Image $mainImage = null;

    public function __construct(Image ...$images)
    {
        parent::__construct(...$images);
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
        if (0 === $this->count()) {
            return null;
        }

        if ($this->mainImage) {
            return $this->mainImage;
        } else {
            $iterator = $this->getIterator();
            $iterator->rewind();

            return $iterator->current();
        }
    }

    public function findImageByUUID(UUID $uuid): ?Image
    {
        /** @var Image $image */
        foreach ($this->getIterator() as $image) {
            if ($image->getMediaObjectId()->sameAs($uuid)) {
                return $image;
            }
        }

        return null;
    }
}
