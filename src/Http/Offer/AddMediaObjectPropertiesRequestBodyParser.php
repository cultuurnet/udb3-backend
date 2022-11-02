<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\Repository\AggregateNotFoundException;
use Broadway\Repository\Repository;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use Psr\Http\Message\ServerRequestInterface;

final class AddMediaObjectPropertiesRequestBodyParser implements RequestBodyParser
{
    private Repository $mediaRepository;

    public function __construct(Repository $mediaRepository)
    {
        $this->mediaRepository = $mediaRepository;
    }

    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $data = (array) $request->getParsedBody();

        $imageId = $data['mediaObjectId'];

        try {
            $this->mediaRepository->load($imageId);
        } catch (AggregateNotFoundException $exception) {
            throw ApiProblem::imageNotFound($imageId);
        }

        $convertedData = [
            'mediaObjectId' => $imageId,
        ];

        return $request->withParsedBody((object) $convertedData);
    }
}
