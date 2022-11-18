<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Event\Commands\UpdateTypicalAgeRange as EventUpdateTypicalAgeRange;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Offer\AgeRange;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Place\Commands\UpdateTypicalAgeRange as PlaceUpdateTypicalAgeRange;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class UpdateTypicalAgeRangeRequestHandler implements RequestHandlerInterface
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
            new JsonSchemaValidatingRequestBodyParser(
                JsonSchemaLocator::getSchemaFileByOfferType(
                    $offerType,
                    JsonSchemaLocator::EVENT_TYPICAL_AGE_RANGE_PUT,
                    JsonSchemaLocator::PLACE_TYPICAL_AGE_RANGE_PUT,
                )
            ),
        );

        $parsedBody = $requestBodyParser->parse($request);

        $ageRange = AgeRange::fromString($bodyContent->typicalAgeRange);

        if ($offerType->sameAs(OfferType::event())) {
            $updateTypicalAgeRange = new EventUpdateTypicalAgeRange(
                $offerId,
                $ageRange
            );
        } else {
            $updateTypicalAgeRange = new PlaceUpdateTypicalAgeRange(
                $offerId,
                $ageRange
            );
        }

        $this->commandBus->dispatch($updateTypicalAgeRange);

        return new NoContentResponse();
    }
}
