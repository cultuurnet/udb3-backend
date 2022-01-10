<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class GetHistoryRequestHandler implements RequestHandlerInterface
{
    private DocumentRepository $eventHistoryDocumentRepository;
    private DocumentRepository $placeHistoryDocumentRepository;
    private bool $currentUserIsGodUser;

    public function __construct(
        DocumentRepository $eventHistoryDocumentRepository,
        DocumentRepository $placeHistoryDocumentRepository,
        bool $currentUserIsGodUser
    ) {
        $this->eventHistoryDocumentRepository = $eventHistoryDocumentRepository;
        $this->placeHistoryDocumentRepository = $placeHistoryDocumentRepository;
        $this->currentUserIsGodUser = $currentUserIsGodUser;
    }

    /**
     * @throws ApiProblem
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $offerType = $routeParameters->getOfferType();
        $offerId = $routeParameters->getOfferId();

        if (!$this->currentUserIsGodUser) {
            throw ApiProblem::forbidden(
                sprintf(
                    'Current user/client does not have enough permissions to access %s history.',
                    $offerType->sameAs(OfferType::EVENT()) ? 'event' : 'place'
                )
            );
        }

        try {
            $historyDocument = $this->getDocumentRepository($offerType)->fetch($offerId);
        } catch (DocumentDoesNotExist $e) {
            throw ApiProblem::offerNotFound($offerType, $offerId);
        }

        $decoded = $historyDocument->getAssocBody();
        $history = array_reverse(array_values($decoded));

        return new JsonResponse($history, 200);
    }

    private function getDocumentRepository(OfferType $offerType): DocumentRepository
    {
        if ($offerType->sameAs(OfferType::EVENT())) {
            return $this->eventHistoryDocumentRepository;
        }
        if ($offerType->sameAs(OfferType::PLACE())) {
            return $this->placeHistoryDocumentRepository;
        }
        throw ApiProblem::internalServerError('Unknown offer type "' . $offerType->toString() . '"');
    }
}
