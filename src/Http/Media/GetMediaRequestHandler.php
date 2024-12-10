<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Media;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Media\MediaManager;
use CultuurNet\UDB3\Media\MediaObjectNotFoundException;
use CultuurNet\UDB3\Media\MediaUrlMapping;
use CultuurNet\UDB3\Media\Serialization\MediaObjectSerializer;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class GetMediaRequestHandler implements RequestHandlerInterface
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

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $id = $routeParameters->get('id');

        try {
            $mediaObject = $this->mediaManager->get(new Uuid($id));
        } catch (MediaObjectNotFoundException | InvalidArgumentException $e) {
            throw ApiProblem::mediaObjectNotFound($id);
        }

        $serializedMediaObject = $this->serializer->serialize($mediaObject);

        $serializedMediaObject['contentUrl'] = $this->mediaUrlMapping->getUpdatedUrl($serializedMediaObject['contentUrl']);
        $serializedMediaObject['thumbnailUrl'] = $this->mediaUrlMapping->getUpdatedUrl($serializedMediaObject['thumbnailUrl']);

        return new JsonResponse($serializedMediaObject);
    }
}
