<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class DeparturePlacesLimitLoggerTest extends TestCase
{
    private LoggerInterface&MockObject $logger;
    private DeparturePlacesLimitLogger $departurePlacesLimitLogger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->departurePlacesLimitLogger = new DeparturePlacesLimitLogger($this->logger);
    }

    /**
     * @test
     */
    public function it_logs_an_error_when_departure_places_limit_is_exceeded(): void
    {
        $eventId = '609a8214-51c9-48c0-903f-840a4f38852f';
        $apiProblem = ApiProblem::bodyInvalidData(
            new SchemaError('/', 'Array should have at most 20 items, 21 found')
        );

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Departure places limit exceeded for event ' . $eventId . ': Array should have at most 20 items, 21 found');

        $this->departurePlacesLimitLogger->logIfLimitExceeded($apiProblem, $eventId, '/');
    }

    /**
     * @test
     */
    public function it_logs_an_error_when_departure_places_limit_is_exceeded_on_nested_pointer(): void
    {
        $eventId = '609a8214-51c9-48c0-903f-840a4f38852f';
        $apiProblem = ApiProblem::bodyInvalidData(
            new SchemaError('/departurePlaces', 'Array should have at most 20 items, 21 found')
        );

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Departure places limit exceeded for event ' . $eventId . ': Array should have at most 20 items, 21 found');

        $this->departurePlacesLimitLogger->logIfLimitExceeded($apiProblem, $eventId, '/departurePlaces');
    }

    /**
     * @test
     */
    public function it_does_not_log_when_the_error_is_on_a_different_pointer(): void
    {
        $eventId = '609a8214-51c9-48c0-903f-840a4f38852f';
        $apiProblem = ApiProblem::bodyInvalidData(
            new SchemaError('/someOtherProperty', 'Array should have at most 20 items, 21 found')
        );

        $this->logger->expects($this->never())
            ->method('error');

        $this->departurePlacesLimitLogger->logIfLimitExceeded($apiProblem, $eventId, '/departurePlaces');
    }

    /**
     * @test
     */
    public function it_does_not_log_when_the_error_is_not_a_max_items_error(): void
    {
        $eventId = '609a8214-51c9-48c0-903f-840a4f38852f';
        $apiProblem = ApiProblem::bodyInvalidData(
            new SchemaError('/departurePlaces', 'The data (object) must match the type: array')
        );

        $this->logger->expects($this->never())
            ->method('error');

        $this->departurePlacesLimitLogger->logIfLimitExceeded($apiProblem, $eventId, '/departurePlaces');
    }
}
