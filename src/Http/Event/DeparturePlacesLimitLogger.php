<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use Psr\Log\LoggerInterface;

final class DeparturePlacesLimitLogger
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function logIfLimitExceeded(ApiProblem $apiProblem, string $eventId, string $jsonPointer): void
    {
        foreach ($apiProblem->getSchemaErrors() as $schemaError) {
            if (
                $schemaError->getJsonPointer() === $jsonPointer
                && str_contains($schemaError->getError(), 'Array should have at most')
            ) {
                $this->logger->error(
                    'Departure places limit exceeded for event ' . $eventId . ': ' . $schemaError->getError()
                );
            }
        }
    }
}
