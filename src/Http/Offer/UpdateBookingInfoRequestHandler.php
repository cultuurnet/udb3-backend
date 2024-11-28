<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Event\Commands\UpdateBookingInfo as EventUpdateBookingInfo;
use CultuurNet\UDB3\Http\Request\Body\DenormalizingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Contact\BookingInfoDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Contact\BookingInfo;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Place\Commands\UpdateBookingInfo as PlaceUpdateBookingInfo;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class UpdateBookingInfoRequestHandler implements RequestHandlerInterface
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

        $parser = RequestBodyParserFactory::createBaseParser(
            new LegacyUpdateBookingInfoRequestBodyParser(),
            new JsonSchemaValidatingRequestBodyParser(
                JsonSchemaLocator::getSchemaFileByOfferType(
                    $offerType,
                    JsonSchemaLocator::EVENT_BOOKING_INFO,
                    JsonSchemaLocator::PLACE_BOOKING_INFO
                )
            ),
            new BookingInfoValidatingRequestBodyParser(),
            new DenormalizingRequestBodyParser(new BookingInfoDenormalizer(), BookingInfo::class)
        );
        $request = $parser->parse($request);

        /** @var BookingInfo $bookingInfo */
        $bookingInfo = $request->getParsedBody();

        if ($routeParameters->getOfferType()->sameAs(OfferType::event())) {
            $updateBookingInfo = new EventUpdateBookingInfo($offerId, $bookingInfo);
        } else {
            $updateBookingInfo = new PlaceUpdateBookingInfo($offerId, $bookingInfo);
        }
        $this->commandBus->dispatch($updateBookingInfo);

        return new NoContentResponse();
    }
}
