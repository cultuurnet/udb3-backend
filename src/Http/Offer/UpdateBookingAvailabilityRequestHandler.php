<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\Body\DenormalizingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\BookingAvailabilityDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Offer\Commands\UpdateBookingAvailability;
use CultuurNet\UDB3\Offer\CalendarTypeNotSupported;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class UpdateBookingAvailabilityRequestHandler implements RequestHandlerInterface
{
    private CommandBus $commandBus;

    public function __construct(
        CommandBus $commandBus
    ) {
        $this->commandBus = $commandBus;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $offerType = $routeParameters->getOfferType();
        $offerId = $routeParameters->getOfferId();

        $parser = RequestBodyParserFactory::createBaseParser(
            new JsonSchemaValidatingRequestBodyParser(
                JsonSchemaLocator::getSchemaFileByOfferType(
                    $offerType,
                    JsonSchemaLocator::EVENT_BOOKING_AVAILABILITY,
                    JsonSchemaLocator::PLACE_BOOKING_AVAILABILITY
                )
            ),
            new DenormalizingRequestBodyParser(new BookingAvailabilityDenormalizer(), BookingAvailability::class)
        );

        /** @var BookingAvailability $bookingAvailability */
        $bookingAvailability = $parser->parse($request)->getParsedBody();

        try {
            $this->commandBus->dispatch(
                new UpdateBookingAvailability(
                    $offerId,
                    $bookingAvailability
                )
            );
        } catch (CalendarTypeNotSupported $exception) {
            throw ApiProblem::calendarTypeNotSupported($exception->getMessage());
        }

        return new NoContentResponse();
    }
}
