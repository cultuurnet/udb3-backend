<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Offer\Commands\UpdateBookingAvailability;
use CultuurNet\UDB3\Offer\CalendarTypeNotSupported;
use CultuurNet\UDB3\Offer\ValueObjects\BookingAvailability;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class UpdateBookingAvailabilityRequestHandler
{
    private CommandBus $commandBus;

    private UpdateBookingAvailabilityRequestBodyParser $updateBookingAvailabilityParser;

    public function __construct(
        CommandBus $commandBus,
        UpdateBookingAvailabilityRequestBodyParser $updateBookingAvailabilityParser
    ) {
        $this->commandBus = $commandBus;
        $this->updateBookingAvailabilityParser = $updateBookingAvailabilityParser;
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
