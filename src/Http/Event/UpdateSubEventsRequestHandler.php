<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Event\Commands\UpdateSubEvents;
use CultuurNet\UDB3\Event\ValueObjects\Status;
use CultuurNet\UDB3\Event\ValueObjects\StatusReason;
use CultuurNet\UDB3\Event\ValueObjects\StatusType;
use CultuurNet\UDB3\Event\ValueObjects\SubEventUpdate;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Request\RequestHandler;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\ValueObjects\BookingAvailability;
use CultuurNet\UDB3\Offer\ValueObjects\BookingAvailabilityType;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class UpdateSubEventsRequestHandler implements RequestHandler
{
    private CommandBus $commandBus;

    private RequestBodyParser $updateSubEventsParser;

    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;

        $this->updateSubEventsParser = RequestBodyParserFactory::createBaseParser(
            JsonSchemaValidatingRequestBodyParser::fromFile(JsonSchemaLocator::EVENT_SUB_EVENT_PATCH)
        );
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $eventId = $routeParameters->get('eventId');

        $updates = $this->updateSubEventsParser->parse($request)->getParsedBody();

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
                    new BookingAvailability(BookingAvailabilityType::fromNative($update['bookingAvailability']['type']))
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
