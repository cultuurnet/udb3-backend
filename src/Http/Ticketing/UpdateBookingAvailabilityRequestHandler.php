<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Ticketing;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Event\Commands\UpdateBookingAvailabilityByReservationLink;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Auth\Jwt\JsonWebToken;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailabilityType;
use CultuurNet\UDB3\Ticketing\Repositories\TicketingRegistrationRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class UpdateBookingAvailabilityRequestHandler implements RequestHandlerInterface
{
    /**
     * @param string[] $allowedClientIds
     */
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly TicketingRegistrationRepository $ticketingRegistrationRepository,
        private readonly JsonWebToken $jsonWebToken,
        private readonly array $allowedClientIds
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!in_array($this->jsonWebToken->getClientId(), $this->allowedClientIds, true)) {
            throw ApiProblem::forbidden('Only ticket sales providers can update the availability of reservation links.');
        }

        $parser = RequestBodyParserFactory::createBaseParser(
            new JsonSchemaValidatingRequestBodyParser(JsonSchemaLocator::TICKETING_BOOKING_AVAILABILITY)
        );
        /** @var object{identifier: string, type: string} $data */
        $data = $parser->parse($request)->getParsedBody();

        $registrations = $this->ticketingRegistrationRepository->findByIdentifier($data->identifier);
        if ($registrations->isEmpty()) {
            throw ApiProblem::urlNotFound(
                sprintf('No reservation link is registered with identifier "%s".', $data->identifier)
            );
        }

        // The waitingListUrl in the body is ignored until bookingAvailability supports it (III-7525).
        $bookingAvailability = new BookingAvailability(new BookingAvailabilityType($data->type));
        foreach ($registrations as $registration) {
            $this->commandBus->dispatch(
                new UpdateBookingAvailabilityByReservationLink(
                    $registration->getEventId()->toString(),
                    $registration->getReservationLink(),
                    $bookingAvailability
                )
            );
        }

        return new NoContentResponse();
    }
}
