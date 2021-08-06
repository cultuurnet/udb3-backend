<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblems;
use CultuurNet\UDB3\Http\Response\ApiProblemJsonResponse;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Offer\Commands\UpdateBookingAvailability;
use CultuurNet\UDB3\Offer\CalendarTypeNotSupported;
use CultuurNet\UDB3\Offer\ValueObjects\BookingAvailability;
use Symfony\Component\HttpFoundation\Request;
use Psr\Http\Message\ResponseInterface;

final class UpdateBookingAvailabilityRequestHandler
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var UpdateBookingAvailabilityValidator
     */
    private $updateBookingAvailabilityValidator;

    public function __construct(
        CommandBus $commandBus,
        UpdateBookingAvailabilityValidator $updateBookingAvailabilityValidator
    ) {
        $this->commandBus = $commandBus;
        $this->updateBookingAvailabilityValidator = $updateBookingAvailabilityValidator;
    }

    public function handle(Request $request, string $offerId): ResponseInterface
    {
        $data = json_decode($request->getContent(), true);

        $this->updateBookingAvailabilityValidator->validate($data);

        try {
            $this->commandBus->dispatch(
                new UpdateBookingAvailability($offerId, BookingAvailability::fromNative($data['type']))
            );
        } catch (CalendarTypeNotSupported $exception) {
            return new ApiProblemJsonResponse(
                ApiProblems::calendarTypeNotSupported($exception->getMessage())
            );
        }

        return new NoContentResponse();
    }
}
