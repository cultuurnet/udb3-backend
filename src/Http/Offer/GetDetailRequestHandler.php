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
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\Permission\PermissionVoter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class GetDetailRequestHandler implements RequestHandlerInterface
{
    private OfferJsonDocumentReadRepository $offerJsonDocumentReadRepository;
    private TurtleResponseFactory $placeTurtleResponseFactory;
    private TurtleResponseFactory $eventTurtleResponseFactory;
    private PermissionVoter $permissionVoter;
    private ?string $currentUserId;

    public function __construct(
        OfferJsonDocumentReadRepository $offerJsonDocumentReadRepository,
        TurtleResponseFactory $placeTurtleResponseFactory,
        TurtleResponseFactory $eventTurtleResponseFactory,
        PermissionVoter $permissionVoter,
        ?string $currentUserId
    ) {
        $this->offerJsonDocumentReadRepository = $offerJsonDocumentReadRepository;
        $this->placeTurtleResponseFactory = $placeTurtleResponseFactory;
        $this->eventTurtleResponseFactory = $eventTurtleResponseFactory;
        $this->permissionVoter = $permissionVoter;
        $this->currentUserId = $currentUserId;
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
        $embedCapacities = $queryParameters->getAsBoolean('embedCapacities');

        $jsonDocument = $this->offerJsonDocumentReadRepository->fetch($offerType, $offerId, $includeMetadata);

        if (!$embedUiTPASPrices) {
            $jsonDocument = $this->removeUiTPASPrices($jsonDocument);
        }

        if (!$embedCapacities || !$this->canEdit($offerId)) {
            $jsonDocument = $this->removeCapacities($jsonDocument);
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

    private function canEdit(string $offerId): bool
    {
        return $this->currentUserId !== null &&
            $this->permissionVoter->isAllowed(Permission::aanbodBewerken(), $offerId, $this->currentUserId);
    }

    private function removeCapacities(JsonDocument $jsonDocument): JsonDocument
    {
        return $jsonDocument->applyAssoc(
            function (array $json) {
                if (!isset($json['subEvent'])) {
                    return $json;
                }
                $json['subEvent'] = array_map(
                    static function (array $subEvent) {
                        unset($subEvent['capacity']);
                        return $subEvent;
                    },
                    $json['subEvent']
                );
                return $json;
            }
        );
    }
}
