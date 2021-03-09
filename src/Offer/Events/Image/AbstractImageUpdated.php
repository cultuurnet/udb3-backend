<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Events\Image;

use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
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
     * @var CopyrightHolder
     */
    protected $copyrightHolder;

    final public function __construct(
        string $itemId,
        UUID $mediaObjectId,
        StringLiteral $description,
        CopyrightHolder $copyrightHolder
    ) {
        parent::__construct($itemId);
        $this->mediaObjectId = $mediaObjectId;
        $this->description = $description;
        $this->copyrightHolder = $copyrightHolder;
    }


    public function getMediaObjectId(): UUID
    {
        return $this->mediaObjectId;
    }

    public function getDescription(): StringLiteral
    {
        return $this->description;
    }

    public function getCopyrightHolder(): CopyrightHolder
    {
        return $this->copyrightHolder;
    }

    public function serialize(): array
    {
        return parent::serialize() +  [
            'media_object_id' => (string) $this->mediaObjectId,
            'description' => (string) $this->description,
            'copyright_holder' => $this->copyrightHolder->toString(),
        ];
    }

    public static function deserialize(array $data): AbstractImageUpdated
    {
        return new static(
            $data['item_id'],
            new UUID($data['media_object_id']),
            new StringLiteral($data['description']),
            new CopyrightHolder($data['copyright_holder'])
        );
    }
}
