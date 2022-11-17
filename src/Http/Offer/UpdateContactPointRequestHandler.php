<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Event\Commands\UpdateContactPoint as EventUpdateContactPoint;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Place\Commands\UpdateContactPoint as PlaceUpdateContactPoint;
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
        $offerType = $routeParameters->getOfferType();

        $bodyContent = Json::decode($request->getBody()->getContents());

        $requestBodyParser = RequestBodyParserFactory::createBaseParser(
            new LegacyContactPointRequestBodyParser(),
            new JsonSchemaValidatingRequestBodyParser(
                JsonSchemaLocator::getSchemaFileByOfferType(
                    $offerType,
                    JsonSchemaLocator::EVENT_CONTACT_POINT_PUT,
                    JsonSchemaLocator::PLACE_CONTACT_POINT_PUT,
                )
            ),
        );

        $request = $requestBodyParser->parse($request);

        $contactPoint = new ContactPoint(
            $bodyContent->contactPoint->phone,
            $bodyContent->contactPoint->email,
            $bodyContent->contactPoint->url
        );

        if ($offerType->sameAs(OfferType::event())) {
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
