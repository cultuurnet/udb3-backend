<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Ticketing;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Auth\Jwt\JsonWebToken;
use CultuurNet\UDB3\Http\Request\QueryParameters;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Ticketing\Repositories\TicketingRegistrationRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class GetRegistrationsRequestHandler implements RequestHandlerInterface
{
    /**
     * @param string[] $allowedClientIds
     */
    public function __construct(
        private readonly TicketingRegistrationRepository $ticketingRegistrationRepository,
        private readonly IriGeneratorInterface $eventIriGenerator,
        private readonly JsonWebToken $jsonWebToken,
        private readonly array $allowedClientIds
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!in_array($this->jsonWebToken->getClientId(), $this->allowedClientIds, true)) {
            throw ApiProblem::forbidden('Only ticketing partners can look up the events of registrations.');
        }

        $identifiers = (new QueryParameters($request))->getAsList('identifiers');
        if ($identifiers === []) {
            throw ApiProblem::queryParameterMissing('identifiers');
        }

        // The identifier column is case-insensitive, so group on the lowercase identifier.
        $eventIdsPerIdentifier = [];
        foreach ($this->ticketingRegistrationRepository->findByIdentifiers($identifiers) as $registration) {
            $eventId = $registration->getEventId()->toString();
            $eventIdsPerIdentifier[strtolower((string) $registration->getIdentifier())][$eventId] = $eventId;
        }

        $registrations = array_map(
            fn (string $identifier): array => [
                'identifier' => $identifier,
                'events' => array_map(
                    fn (string $eventId): string => $this->eventIriGenerator->iri($eventId),
                    array_values($eventIdsPerIdentifier[strtolower($identifier)] ?? [])
                ),
            ],
            $identifiers
        );

        return new JsonResponse(['registrations' => $registrations]);
    }
}
