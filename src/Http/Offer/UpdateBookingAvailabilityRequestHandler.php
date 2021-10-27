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
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Offer\ValueObjects\BookingAvailability as LegacyBookingAvailability;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

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
            new JsonSchemaValidatingRequestBodyParser($this->getSchemaLocation($offerType)),
            new DenormalizingRequestBodyParser(new BookingAvailabilityDenormalizer(), BookingAvailability::class)
        );

        /** @var BookingAvailability $bookingAvailability */
        $bookingAvailability = $parser->parse($request)->getParsedBody();

        try {
            $this->commandBus->dispatch(
                new UpdateBookingAvailability(
                    $offerId,
                    LegacyBookingAvailability::fromUdb3ModelBookingAvailability($bookingAvailability)
                )
            );
        } catch (CalendarTypeNotSupported $exception) {
            throw ApiProblem::calendarTypeNotSupported($exception->getMessage());
        }

        return new NoContentResponse();
    }

    private function getSchemaLocation(OfferType $offerType): string
    {
        if ($offerType->sameValueAs(OfferType::EVENT())) {
            return JsonSchemaLocator::EVENT_BOOKING_AVAILABILITY;
        }
        if ($offerType->sameValueAs(OfferType::PLACE())) {
            return JsonSchemaLocator::PLACE_BOOKING_AVAILABILITY;
        }
        throw new RuntimeException('No schema found for unknown offer type ' . $offerType->toNative());
    }
}
