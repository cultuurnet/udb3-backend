<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Events\Image;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Offer\Events\AbstractEvent;
use CultuurNet\UDB3\StringLiteral;

abstract class AbstractImageUpdated extends AbstractEvent
{
    protected UUID $mediaObjectId;

    /**
     * @var StringLiteral
     */
    protected $description;

    /**
     * @var CopyrightHolder
     */
    protected $copyrightHolder;

    /**
     * Nullable because this was missing in the past, so we don't have historical data for this.
     * Also we cannot default it to `nl`, because an image could have been added in another language and that language
     * needs to be respected.
     */
    private ?string $language;

    final public function __construct(
        string $itemId,
        UUID $mediaObjectId,
        StringLiteral $description,
        CopyrightHolder $copyrightHolder,
        ?string $language = null
    ) {
        parent::__construct($itemId);
        $this->mediaObjectId = $mediaObjectId;
        $this->description = $description;
        $this->copyrightHolder = $copyrightHolder;
        $this->language = $language;
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

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function serialize(): array
    {
        return parent::serialize() +  [
            'media_object_id' => $this->mediaObjectId->toString(),
            'description' => (string) $this->description,
            'copyright_holder' => $this->copyrightHolder->toString(),
            'language' => $this->language,
        ];
    }

    public static function deserialize(array $data): AbstractImageUpdated
    {
        return new static(
            $data['item_id'],
            new UUID($data['media_object_id']),
            new StringLiteral($data['description']),
            new CopyrightHolder($data['copyright_holder']),
            $data['language'] ?? null
        );
    }
}
