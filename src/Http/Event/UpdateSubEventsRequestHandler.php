<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Event\Commands\UpdateSubEvents;
use CultuurNet\UDB3\Event\ValueObjects\Status;
use CultuurNet\UDB3\Event\ValueObjects\StatusReason;
use CultuurNet\UDB3\Event\ValueObjects\StatusType;
use CultuurNet\UDB3\Event\ValueObjects\SubEventUpdate;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\ValueObjects\BookingAvailability;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class UpdateSubEventsRequestHandler
{
    private CommandBus $commandBus;

    private UpdateSubEventsRequestBodyParser $updateSubEventsParser;

    public function __construct(
        CommandBus $commandBus,
        UpdateSubEventsRequestBodyParser $updateSubEventsParser
    ) {
        $this->commandBus = $commandBus;
        $this->updateSubEventsParser = $updateSubEventsParser;
    }

    public function handle(ServerRequestInterface $request, string $eventId): ResponseInterface
    {
        $updates = $this->updateSubEventsParser->parse($request);

        $updateSubEvents = [];
        foreach ($updates as $update) {
            $subEventUpdate = new SubEventUpdate($update['id']);

            if (isset($update['status'])) {
                $subEventUpdate = $subEventUpdate->withStatus(
                    new Status(
                        StatusType::fromNative($update['status']['type']),
                        $this->parseReason($update)
                    )
                );
            }

            if (isset($update['bookingAvailability'])) {
                $subEventUpdate = $subEventUpdate->withBookingAvailability(
                    BookingAvailability::fromNative($update['bookingAvailability']['type'])
                );
            }

            $updateSubEvents[] = $subEventUpdate;
        }

        $this->commandBus->dispatch(new UpdateSubEvents($eventId, ...$updateSubEvents));

        return new NoContentResponse();
    }

    /**
     * @return StatusReason[]
     */
    private function parseReason(array $data): array
    {
        if (!isset($data['status']['reason'])) {
            return [];
        }

        $reason = [];
        foreach ($data['status']['reason'] as $language => $translatedReason) {
            $reason[] = new StatusReason(new Language($language), $translatedReason);
        }

        return $reason;
    }
}
