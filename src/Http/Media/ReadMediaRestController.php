<?php

namespace CultuurNet\UDB3\Symfony\Media;

use Broadway\Repository\AggregateNotFoundException;
use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\Media\MediaManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use ValueObjects\Identity\UUID;

class ReadMediaRestController
{
    /**
     * @var MediaManager;
     */
    protected $mediaManager;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    public function __construct(
        MediaManager $mediaManager,
        SerializerInterface $serializer
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

        $serializedMediaObject = $this->serializer
            ->serialize($mediaObject, 'json-ld');

        return JsonResponse::create($serializedMediaObject);
    }
}
