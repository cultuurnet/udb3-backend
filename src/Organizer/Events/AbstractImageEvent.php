<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Image;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;

abstract class AbstractImageEvent implements Serializable
{
    private string $organizerId;

    private string $imageId;

    private string $language;

    private string $description;

    private string $copyrightHolder;

    private ?bool $isAiGenerated;

    final public function __construct(
        string $organizerId,
        string $imageId,
        string $language,
        string $description,
        string $copyrightHolder,
        ?bool $isAiGenerated = null
    ) {
        $this->organizerId = $organizerId;
        $this->imageId = $imageId;
        $this->language = $language;
        $this->description = $description;
        $this->copyrightHolder = $copyrightHolder;
        $this->isAiGenerated = $isAiGenerated;
    }

    public function getOrganizerId(): string
    {
        return $this->organizerId;
    }

    public function getImageId(): string
    {
        return $this->imageId;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getCopyrightHolder(): string
    {
        return $this->copyrightHolder;
    }

    public function isAiGenerated(): ?bool
    {
        return $this->isAiGenerated;
    }

    public function getImage(): Image
    {
        return new Image(
            new Uuid($this->imageId),
            new Language($this->language),
            new Description($this->description),
            new CopyrightHolder($this->copyrightHolder),
            $this->isAiGenerated
        );
    }

    public function serialize(): array
    {
        $data = [
            'organizerId' => $this->organizerId,
            'imageId' => $this->imageId,
            'language' => $this->language,
            'description' => $this->description,
            'copyrightHolder' => $this->copyrightHolder,
        ];
        if ($this->isAiGenerated !== null) {
            $data['isAiGenerated'] = $this->isAiGenerated;
        }
        return $data;
    }

    public static function deserialize(array $data): AbstractImageEvent
    {
        return new static(
            $data['organizerId'],
            $data['imageId'],
            $data['language'],
            $data['description'],
            $data['copyrightHolder'],
            $data['isAiGenerated'] ?? null
        );
    }
}
