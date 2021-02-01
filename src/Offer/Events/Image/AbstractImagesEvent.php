<?php

namespace CultuurNet\UDB3\Offer\Events\Image;

use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Media\ImageCollection;
use CultuurNet\UDB3\Offer\Events\AbstractEvent;

abstract class AbstractImagesEvent extends AbstractEvent
{
    /**
     * @var ImageCollection
     */
    protected $images;

    final public function __construct(string $eventId, ImageCollection $images)
    {
        parent::__construct($eventId);
        $this->images = $images;
    }

    public function getImages(): ImageCollection
    {
        return $this->images;
    }

    public function serialize(): array
    {
        $serializedData =  parent::serialize() + array(
            'images' => array_map(
                function (Image $image) {
                    return $image->serialize();
                },
                $this->images->toArray()
            ),
        );

        $mainImage = $this->images->getMain();
        if ($mainImage) {
            $serializedData[] = $mainImage->serialize();
        }

        return $serializedData;
    }

    public static function deserialize(array $data): AbstractImagesEvent
    {
        $images = ImageCollection::fromArray(
            array_map(
                function ($imageData) {
                    return Image::deserialize($imageData);
                },
                $data['images']
            )
        );

        return new static(
            $data['item_id'],
            isset($data['main_image'])
                ? $images->withMain(Image::deserialize($data['main_image']))
                : $images
        );
    }
}
