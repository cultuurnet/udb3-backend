<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\QueryParameters;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonLdResponse;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\OfferJsonDocumentReadRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class GetDetailRequestHandler implements RequestHandlerInterface
{
    private OfferJsonDocumentReadRepository $offerJsonDocumentReadRepository;

    public function __construct(OfferJsonDocumentReadRepository $offerJsonDocumentReadRepository)
    {
        $this->offerJsonDocumentReadRepository = $offerJsonDocumentReadRepository;
    }

    /**
     * @throws ApiProblem
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $offerType = $routeParameters->getOfferType();
        $offerId = $routeParameters->getOfferId();

        $queryParameters = new QueryParameters($request);
        $includeMetadata = $queryParameters->getAsBoolean('includeMetadata');

        $jsonDocument = $this->offerJsonDocumentReadRepository->fetch($offerType, $offerId, $includeMetadata);

        return new JsonLdResponse($jsonDocument->getAssocBody());
    }
}
