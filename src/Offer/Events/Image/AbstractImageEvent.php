<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Events\Image;

use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Offer\Events\AbstractEvent;

abstract class AbstractImageEvent extends AbstractEvent
{
    protected Image $image;

    final public function __construct(string $itemId, Image $image)
    {
        parent::__construct($itemId);
        $this->image = $image;
    }

    public function getImage(): Image
    {
        return $this->image;
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'image' => $this->image->serialize(),
        ];
    }

    public static function deserialize(array $data): AbstractImageEvent
    {
        return new static(
            $data['item_id'],
            Image::deserialize($data['image'])
        );
    }
}
