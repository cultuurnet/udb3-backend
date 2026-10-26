<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Events\Image;

use CultuurNet\UDB3\Offer\Events\AbstractEvent;

abstract class AbstractImageUpdated extends AbstractEvent
{
    private string $mediaObjectId;

    private string $description;

    private string $copyrightHolder;

    /**
     * Nullable because this was missing in the past, so we don't have historical data for this.
     * Also we cannot default it to `nl`, because an image could have been added in another language and that language
     * needs to be respected.
     */
    private ?string $language;

    /**
     * Nullable because this was introduced later. A null value means the aiGenerated value of the image is unchanged.
     */
    private ?bool $aiGenerated;

    final public function __construct(
        string $itemId,
        string $mediaObjectId,
        string $description,
        string $copyrightHolder,
        ?string $language = null,
        ?bool $aiGenerated = null
    ) {
        parent::__construct($itemId);
        $this->mediaObjectId = $mediaObjectId;
        $this->description = $description;
        $this->copyrightHolder = $copyrightHolder;
        $this->language = $language;
        $this->aiGenerated = $aiGenerated;
    }

    public function getMediaObjectId(): string
    {
        return $this->mediaObjectId;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getCopyrightHolder(): string
    {
        return $this->copyrightHolder;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function getAiGenerated(): ?bool
    {
        return $this->aiGenerated;
    }

    public function serialize(): array
    {
        $data = parent::serialize() +  [
            'media_object_id' => $this->mediaObjectId,
            'description' => $this->description,
            'copyright_holder' => $this->copyrightHolder,
        ];
        if ($this->language) {
            $data['language'] = $this->language;
        }
        if ($this->aiGenerated !== null) {
            $data['ai_generated'] = $this->aiGenerated;
        }
        return $data;
    }

    public static function deserialize(array $data): AbstractImageUpdated
    {
        return new static(
            $data['item_id'],
            $data['media_object_id'],
            $data['description'],
            $data['copyright_holder'],
            $data['language'] ?? null,
            $data['ai_generated'] ?? null
        );
    }
}
