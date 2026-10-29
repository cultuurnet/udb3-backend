<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media\Events;

use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;

final class MediaObjectAiGeneratedUpdated implements Serializable
{
    private Uuid $mediaObjectId;

    private bool $isAiGenerated;

    public function __construct(Uuid $mediaObjectId, bool $isAiGenerated)
    {
        $this->mediaObjectId = $mediaObjectId;
        $this->isAiGenerated = $isAiGenerated;
    }

    public function getMediaObjectId(): Uuid
    {
        return $this->mediaObjectId;
    }

    public function isAiGenerated(): bool
    {
        return $this->isAiGenerated;
    }

    public function serialize(): array
    {
        return [
            'media_object_id' => $this->mediaObjectId->toString(),
            'is_ai_generated' => $this->isAiGenerated,
        ];
    }

    public static function deserialize(array $data): MediaObjectAiGeneratedUpdated
    {
        return new self(
            new Uuid($data['media_object_id']),
            $data['is_ai_generated']
        );
    }
}
