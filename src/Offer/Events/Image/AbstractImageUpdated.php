<?php

namespace CultuurNet\UDB3\Offer\Events\Image;

use CultuurNet\UDB3\Offer\Events\AbstractEvent;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

abstract class AbstractImageUpdated extends AbstractEvent
{
    /**
     * @var UUID
     */
    protected $mediaObjectId;

    /**
     * @var StringLiteral
     */
    protected $description;

    /**
     * @var StringLiteral
     */
    protected $copyrightHolder;

    final public function __construct(
        string $itemId,
        UUID $mediaObjectId,
        StringLiteral $description,
        StringLiteral $copyrightHolder
    ) {
        parent::__construct($itemId);
        $this->mediaObjectId = $mediaObjectId;
        $this->description = $description;
        $this->copyrightHolder = $copyrightHolder;
    }

    /**
     * @return UUID
     */
    public function getMediaObjectId(): UUID
    {
        return $this->mediaObjectId;
    }

    public function getDescription(): StringLiteral
    {
        return $this->description;
    }

    public function getCopyrightHolder(): StringLiteral
    {
        return $this->copyrightHolder;
    }

    public function serialize(): array
    {
        return parent::serialize() +  array(
            'media_object_id' => (string) $this->mediaObjectId,
            'description' => (string) $this->description,
            'copyright_holder' => (string) $this->copyrightHolder,
        );
    }

    public static function deserialize(array $data): AbstractImageUpdated
    {
        return new static(
            $data['item_id'],
            new UUID($data['media_object_id']),
            new StringLiteral($data['description']),
            new StringLiteral($data['copyright_holder'])
        );
    }
}
