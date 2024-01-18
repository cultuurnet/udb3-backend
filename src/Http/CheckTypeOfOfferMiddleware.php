<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http;

use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

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

        try {
            $offerType = $routeParameters->getOfferType();
        } catch (RuntimeException $e) {
            return $handler->handle($request);
        }

        $offerId = $routeParameters->getOfferId();

        if ($offerType === OfferType::event()) {
            $this->eventRepository->fetch($offerId);
        }
        else if ($offerType === OfferType::place()) {
            $this->placeRepository->fetch($offerId);
        }

        return $handler->handle($request);
    }
}
