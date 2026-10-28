<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media\Events;

use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;

final class MediaObjectRenamed implements Serializable
{
    private Uuid $mediaObjectId;

    private Url $sourceLocation;

    public function __construct(Uuid $mediaObjectId, Url $sourceLocation)
    {
        $this->mediaObjectId = $mediaObjectId;
        $this->sourceLocation = $sourceLocation;
    }

    public function getMediaObjectId(): Uuid
    {
        return $this->mediaObjectId;
    }

    public function getSourceLocation(): Url
    {
        return $this->sourceLocation;
    }

    public function serialize(): array
    {
        return [
            'media_object_id' => $this->mediaObjectId->toString(),
            'source_location' => $this->sourceLocation->toString(),
        ];
    }

    public static function deserialize(array $data): MediaObjectRenamed
    {
        return new self(
            new Uuid($data['media_object_id']),
            new Url($data['source_location'])
        );
    }
}
