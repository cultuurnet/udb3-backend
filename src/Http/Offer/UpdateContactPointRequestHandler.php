<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Event\Commands\UpdateContactPoint as EventUpdateContactPoint;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Place\Commands\UpdateContactPoint as PlaceUpdateContactPoint;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class UpdateContactPointRequestHandler implements RequestHandlerInterface
{
    private CommandBus $commandBus;

    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $offerId = $routeParameters->getOfferId();
        $bodyContent = Json::decode($request->getBody()->getContents());

        // @todo Use a data validator and change to an exception so it can be converted to an API problem
        if (
            empty($bodyContent->contactPoint) ||
            !isset(
                $bodyContent->contactPoint->url,
                $bodyContent->contactPoint->email,
                $bodyContent->contactPoint->phone
            )
        ) {
            return new JsonResponse(
                [
                    'error' => 'contactPoint and his properties required',
                ],
                StatusCodeInterface::STATUS_BAD_REQUEST
            );
        }
        $contactPoint = new ContactPoint(
            $bodyContent->contactPoint->phone,
            $bodyContent->contactPoint->email,
            $bodyContent->contactPoint->url
        );

        if ($routeParameters->getOfferType()->sameAs(OfferType::event())) {
            $updateContactPoint = new EventUpdateContactPoint(
                $offerId,
                $contactPoint
            );
        } else {
            $updateContactPoint = new PlaceUpdateContactPoint(
                $offerId,
                $contactPoint
            );
        }

        $this->commandBus->dispatch($updateContactPoint);

        return new NoContentResponse();
    }
}
