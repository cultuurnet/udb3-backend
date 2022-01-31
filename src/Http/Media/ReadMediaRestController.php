<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Media;

use Broadway\Repository\AggregateNotFoundException;
use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\Media\MediaManager;
use CultuurNet\UDB3\Media\MediaUrlMapping;
use CultuurNet\UDB3\Media\Serialization\MediaObjectSerializer;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use Symfony\Component\HttpFoundation\JsonResponse;

final class ReadMediaRestController
{
    private MediaManager $mediaManager;

    private MediaObjectSerializer $serializer;

    private MediaUrlMapping $mediaUrlMapping;

    public function __construct(
        MediaManager $mediaManager,
        MediaObjectSerializer $serializer,
        MediaUrlMapping $mediaUrlMapping
    ) {
        $this->mediaManager = $mediaManager;
        $this->serializer = $serializer;
        $this->mediaUrlMapping = $mediaUrlMapping;
    }

    public function get($id): JsonResponse
    {
        try {
            $mediaObject = $this->mediaManager->get(new UUID($id));
        } catch (AggregateNotFoundException $ex) {
            throw new EntityNotFoundException(
                sprintf('Media with id: %s not found.', $id)
            );
        }

        $serializedMediaObject = $this->serializer->serialize($mediaObject);

        $serializedMediaObject['contentUrl'] = $this->mediaUrlMapping->getUpdatedUrl($serializedMediaObject['contentUrl']);
        $serializedMediaObject['thumbnailUrl'] = $this->mediaUrlMapping->getUpdatedUrl($serializedMediaObject['thumbnailUrl']);

        return JsonResponse::create($serializedMediaObject);
    }
}
