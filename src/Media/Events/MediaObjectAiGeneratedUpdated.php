<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media\Events;

use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;

final class MediaObjectAiGeneratedUpdated implements Serializable
{
    private Uuid $mediaObjectId;

    /**
     * The stored description of the media object, without AI generated suffix.
     * Needed by projectors to rebuild the description, because the suffix is only added on the read side.
     */
    private Description $description;

    private Language $language;

    private bool $isAiGenerated;

    public function __construct(
        Uuid $mediaObjectId,
        Description $description,
        Language $language,
        bool $isAiGenerated
    ) {
        $this->mediaObjectId = $mediaObjectId;
        $this->description = $description;
        $this->language = $language;
        $this->isAiGenerated = $isAiGenerated;
    }

    public function getMediaObjectId(): Uuid
    {
        return $this->mediaObjectId;
    }

    public function getDescription(): Description
    {
        return $this->description;
    }

    public function getLanguage(): Language
    {
        return $this->language;
    }

    public function isAiGenerated(): bool
    {
        return $this->isAiGenerated;
    }

    public function serialize(): array
    {
        return [
            'media_object_id' => $this->mediaObjectId->toString(),
            'description' => $this->description->toString(),
            'language' => $this->language->toString(),
            'is_ai_generated' => $this->isAiGenerated,
        ];
    }

    public static function deserialize(array $data): MediaObjectAiGeneratedUpdated
    {
        return new self(
            new Uuid($data['media_object_id']),
            new Description($data['description']),
            new Language($data['language']),
            $data['is_ai_generated']
        );
    }
}
