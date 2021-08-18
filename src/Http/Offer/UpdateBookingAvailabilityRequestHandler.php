<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Offer\Commands\UpdateBookingAvailability;
use CultuurNet\UDB3\Offer\CalendarTypeNotSupported;
use CultuurNet\UDB3\Offer\ValueObjects\BookingAvailability;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;

final class UpdateBookingAvailabilityRequestHandler
{
    private CommandBus $commandBus;

    private RequestBodyParser $updateBookingAvailabilityParser;

    public function __construct(
        CommandBus $commandBus
    ) {
        $this->commandBus = $commandBus;

        $this->updateBookingAvailabilityParser = RequestBodyParserFactory::createBaseParser(
            JsonSchemaValidatingRequestBodyParser::fromFile(JsonSchemaLocator::EVENT_BOOKING_AVAILABILITY_PUT)
        );
    }

    public function handle(ServerRequestInterface $request, string $offerId): ResponseInterface
    {
        $data = (object) $this->updateBookingAvailabilityParser->parse($request)->getParsedBody();

        try {
            $this->commandBus->dispatch(
                new UpdateBookingAvailability($offerId, BookingAvailability::fromNative($data->type))
            );
        } catch (CalendarTypeNotSupported $exception) {
            throw ApiProblem::calendarTypeNotSupported($exception->getMessage());
        }

        return new NoContentResponse();
    }
}
