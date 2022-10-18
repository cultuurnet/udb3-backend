<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\Event\Commands\UpdateBookingInfo as EventUpdateBookingInfo;
use CultuurNet\UDB3\Http\Deserializer\BookingInfo\BookingInfoJSONDeserializer;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Place\Commands\UpdateBookingInfo as PlaceUpdateBookingInfo;
use CultuurNet\UDB3\StringLiteral;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class UpdateBookingInfoRequestHandler implements RequestHandlerInterface
{
    private JSONDeserializer $bookingInfoDeserializer;
    private CommandBus $commandBus;

    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
        $this->bookingInfoDeserializer = new BookingInfoJSONDeserializer();
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $offerId = $routeParameters->getOfferId();
        $offerType = $routeParameters->getOfferType();

        $parser = RequestBodyParserFactory::createBaseParser(
            new JsonSchemaValidatingRequestBodyParser(
                JsonSchemaLocator::getSchemaFileByOfferType(
                    $offerType,
                    JsonSchemaLocator::EVENT_BOOKING_INFO,
                    JsonSchemaLocator::PLACE_BOOKING_INFO
                )
            )
        );
        $request = $parser->parse($request);

        $bookingInfo = $this->bookingInfoDeserializer->deserialize(new StringLiteral($request->getBody()->getContents()));

        if ($routeParameters->getOfferType()->sameAs(OfferType::event())) {
            $updateBookingInfo = new EventUpdateBookingInfo(
                $offerId,
                $bookingInfo
            );
        } else {
            $updateBookingInfo = new PlaceUpdateBookingInfo(
                $offerId,
                $bookingInfo
            );
        }
        $this->commandBus->dispatch($updateBookingInfo);

        return new NoContentResponse();
    }
}
