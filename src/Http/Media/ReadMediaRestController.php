<?php

namespace CultuurNet\UDB3\Http\Media;

use Broadway\Repository\AggregateNotFoundException;
use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\Media\MediaManager;
use CultuurNet\UDB3\Media\Serialization\MediaObjectSerializer;
use Symfony\Component\HttpFoundation\JsonResponse;
use ValueObjects\Identity\UUID;

class ReadMediaRestController
{
    /**
     * @var MediaManager;
     */
    protected $mediaManager;

    /**
     * @var MediaObjectSerializer
     */
    protected $serializer;

    public function __construct(
        MediaManager $mediaManager,
        MediaObjectSerializer $serializer
    ) {
        $this->mediaManager = $mediaManager;
        $this->serializer = $serializer;
    }

    public function get($id)
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
