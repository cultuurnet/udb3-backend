<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Event\ReadModel\Relations\InMemoryEventRelationsRepository;
use CultuurNet\UDB3\Offer\Commands\DeleteOrganizer;
use PHPUnit\Framework\TestCase;

class EventOrganizerRelationServiceTest extends TestCase
{
    private const ORGANIZER_ID = '6cdf72ad-5d6a-4a0a-82aa-30f038385d9f';

    private const EVENT_IDS = [
        'b1c876d7-48d5-4294-b67a-1787c5cb02e2',
        '4bcb9fd8-1b5b-4b61-8597-03b9d0a46134',
        '8f1b121d-ca12-485d-bb81-5bf205273560',
    ];

    private TraceableCommandBus $commandBus;

    private EventOrganizerRelationService $organizerRelationService;

    public function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $relationRepository = new InMemoryEventRelationsRepository();
        foreach (self::EVENT_IDS as $eventId) {
            $relationRepository->storeRelations($eventId, null, self::ORGANIZER_ID);
        }

        $this->organizerRelationService = new EventOrganizerRelationService(
            $this->commandBus,
            $relationRepository
        );

        $this->commandBus->record();
    }

    /**
     * @test
     */
    public function it_removes_the_organizer_from_all_events(): void
    {
        $this->organizerRelationService->deleteOrganizer(self::ORGANIZER_ID);

        $this->assertEquals(
            [
                new DeleteOrganizer(self::EVENT_IDS[0], self::ORGANIZER_ID),
                new DeleteOrganizer(self::EVENT_IDS[1], self::ORGANIZER_ID),
                new DeleteOrganizer(self::EVENT_IDS[2], self::ORGANIZER_ID),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }
}
