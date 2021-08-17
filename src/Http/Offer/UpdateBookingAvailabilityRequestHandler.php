<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\Body\ContentNegotiationRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\JsonRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Offer\Commands\UpdateBookingAvailability;
use CultuurNet\UDB3\Offer\CalendarTypeNotSupported;
use CultuurNet\UDB3\Offer\ValueObjects\BookingAvailability;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class UpdateBookingAvailabilityRequestHandler
{
    private CommandBus $commandBus;

    private RequestBodyParser $updateBookingAvailabilityParser;

    public function __construct(
        CommandBus $commandBus
    ) {
        $this->commandBus = $commandBus;

        $this->updateBookingAvailabilityParser = (new ContentNegotiationRequestBodyParser())
            ->withJsonRequestBodyParser(
                new JsonSchemaValidatingRequestBodyParser(
                    file_get_contents(__DIR__ . '/../../../vendor/publiq/stoplight-docs-uitdatabank/models/event-bookingAvailability-put.json'),
                    new JsonRequestBodyParser()
                )
            );
    }

    public function handle(ServerRequestInterface $request, string $offerId): ResponseInterface
    {
        $data = $this->updateBookingAvailabilityParser->parse($request);

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
