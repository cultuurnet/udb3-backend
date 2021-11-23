<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Media;

use Broadway\Repository\AggregateNotFoundException;
use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\Media\MediaManager;
use CultuurNet\UDB3\Media\Serialization\MediaObjectSerializer;
use Symfony\Component\HttpFoundation\JsonResponse;
use ValueObjects\Identity\UUID;

final class ReadMediaRestController
{
    private MediaManager $mediaManager;

    private MediaObjectSerializer $serializer;

    public function __construct(
        MediaManager $mediaManager,
        MediaObjectSerializer $serializer
    ) {
        $this->mediaManager = $mediaManager;
        $this->serializer = $serializer;
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

        return JsonResponse::create($serializedMediaObject);
    }
}
