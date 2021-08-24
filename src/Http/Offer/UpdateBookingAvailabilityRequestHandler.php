<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Request\RequestHandler;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\BookingAvailabilityDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Offer\Commands\UpdateBookingAvailability;
use CultuurNet\UDB3\Offer\CalendarTypeNotSupported;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class UpdateBookingAvailabilityRequestHandler implements RequestHandler
{
    private CommandBus $commandBus;
    private RequestBodyParser $parser;
    private BookingAvailabilityDenormalizer $bookingAvailabilityDenormalizer;

    public function __construct(
        CommandBus $commandBus
    ) {
        $this->commandBus = $commandBus;
        $this->parser = RequestBodyParserFactory::createBaseParser(
            JsonSchemaValidatingRequestBodyParser::fromFile(JsonSchemaLocator::OFFER_BOOKING_AVAILABILITY)
        );
        $this->bookingAvailabilityDenormalizer = new BookingAvailabilityDenormalizer();
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $offerId = $routeParameters->get('offerId');

        $data = $this->parser->parse($request)->getParsedBody();

        $bookingAvailability = $this->bookingAvailabilityDenormalizer->denormalize($data, BookingAvailability::class);

        try {
            $this->commandBus->dispatch(
                new UpdateBookingAvailability(
                    $offerId,
                    \CultuurNet\UDB3\Offer\ValueObjects\BookingAvailability::fromUdb3ModelBookingAvailability($bookingAvailability)
                )
            );
        } catch (CalendarTypeNotSupported $exception) {
            throw ApiProblem::calendarTypeNotSupported($exception->getMessage());
        }

        return new NoContentResponse();
    }
}
