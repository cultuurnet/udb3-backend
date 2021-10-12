<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Place;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\QueryParameters;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonLdResponse;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class GetPlaceDetailRequestHandler implements RequestHandlerInterface
{
    private DocumentRepository $documentRepository;

    public function __construct(DocumentRepository $documentRepository)
    {
        $this->documentRepository = $documentRepository;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $placeId = $routeParameters->getPlaceId();
        $queryParameters = new QueryParameters($request);
        $includeMetadata = $queryParameters->getAsBoolean('includeMetadata');

        try {
            $placeDocument = $this->documentRepository->fetch($placeId, $includeMetadata);
        } catch (DocumentDoesNotExist $e) {
            throw ApiProblem::placeNotFound($placeId);
        }

        return new JsonLdResponse($placeDocument->getAssocBody());
    }
}
