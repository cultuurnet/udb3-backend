<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http;

use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CheckTypeOfOfferMiddleware implements MiddlewareInterface
{
    private DocumentRepository $placeRepository;
    private DocumentRepository $eventRepository;

    public function __construct(DocumentRepository $placeRepository, DocumentRepository $eventRepository)
    {
        $this->placeRepository = $placeRepository;
        $this->eventRepository = $eventRepository;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);

        if (!$routeParameters->hasOfferType() || !$routeParameters->hasOfferId()) {
            return $handler->handle($request);
        }

        $offerType = $routeParameters->getOfferType();
        $offerId = $routeParameters->getOfferId();

        if (OfferType::event()->sameAs($offerType)) {
            $this->eventRepository->fetch($offerId);
        }

        if (OfferType::place()->sameAs($offerType)) {
            if ((new LocationId($offerId))->isNilLocation()) {
                return $handler->handle($request);
            }
            $this->placeRepository->fetch($offerId);
        }

        return $handler->handle($request);
    }
}
