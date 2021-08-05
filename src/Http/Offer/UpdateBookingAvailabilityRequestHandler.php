<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblems;
use CultuurNet\UDB3\HttpFoundation\Response\ApiProblemJsonResponse;
use CultuurNet\UDB3\HttpFoundation\Response\NoContent;
use CultuurNet\UDB3\Offer\Commands\UpdateBookingAvailability;
use CultuurNet\UDB3\Offer\UpdateBookingAvailabilityNotSupported;
use CultuurNet\UDB3\Offer\ValueObjects\BookingAvailability;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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

    public function handle(Request $request, string $offerId): Response
    {
        $data = json_decode($request->getContent(), true);

        $this->updateBookingAvailabilityValidator->validate($data);

        try {
            $this->commandBus->dispatch(
                new UpdateBookingAvailability($offerId, BookingAvailability::fromNative($data['type']))
            );
        } catch (UpdateBookingAvailabilityNotSupported $exception) {
            return new ApiProblemJsonResponse(
                ApiProblems::updateBookingAvailabilityNotSupported($exception->getMessage())
            );
        }

        return new NoContent();
    }
}
