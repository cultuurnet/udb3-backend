<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\RDF\TurtleResponseFactory;
use CultuurNet\UDB3\Http\Request\QueryParameters;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonLdResponse;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\OfferJsonDocumentReadRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class GetDetailRequestHandler implements RequestHandlerInterface
{
    private OfferJsonDocumentReadRepository $offerJsonDocumentReadRepository;
    private TurtleResponseFactory $placeTurtleResponseFactory;
    private TurtleResponseFactory $eventTurtleResponseFactory;

    public function __construct(
        OfferJsonDocumentReadRepository $offerJsonDocumentReadRepository,
        TurtleResponseFactory $placeTurtleResponseFactory,
        TurtleResponseFactory $eventTurtleResponseFactory
    ) {
        $this->offerJsonDocumentReadRepository = $offerJsonDocumentReadRepository;
        $this->placeTurtleResponseFactory = $placeTurtleResponseFactory;
        $this->eventTurtleResponseFactory = $eventTurtleResponseFactory;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $offerType = $routeParameters->getOfferType();
        $offerId = $routeParameters->getOfferId();
        $acceptHeader = $request->getHeaderLine('Accept');

        if ($acceptHeader === 'text/turtle') {
            if ($offerType->sameAs(OfferType::place())) {
                return $this->placeTurtleResponseFactory->turtle($offerId);
            }
            return $this->eventTurtleResponseFactory->turtle($offerId);
        }

        $queryParameters = new QueryParameters($request);
        $includeMetadata = $queryParameters->getAsBoolean('includeMetadata');
        $embedUiTPASPrices = $queryParameters->getAsBoolean('embedUitpasPrices');

        $jsonDocument = $this->offerJsonDocumentReadRepository->fetch($offerType, $offerId, $includeMetadata);

        if (!$embedUiTPASPrices) {
            $jsonDocument = $this->removeUiTPASPrices($jsonDocument);
        }

        return new JsonLdResponse($jsonDocument->getAssocBody());
    }

    private function removeUiTPASPrices(JsonDocument $jsonDocument): JsonDocument
    {
        return $jsonDocument->applyAssoc(
            function (array $json) {
                if (!isset($json['priceInfo'])) {
                    return $json;
                }
                $json['priceInfo'] = array_filter(
                    $json['priceInfo'],
                    static fn (array $price) => $price['category'] !== 'uitpas'
                );
                return $json;
            }
        );
    }
}
