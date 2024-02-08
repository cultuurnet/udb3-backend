<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use Broadway\EventStore\TraceableEventStore;
use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Calendar\CalendarType;
use CultuurNet\UDB3\Event\EventRepository;
use CultuurNet\UDB3\Event\Events\AvailableFromUpdated;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\EventDeleted;
use CultuurNet\UDB3\Event\Events\Moderation\Approved;
use CultuurNet\UDB3\Event\Events\Moderation\Published;
use CultuurNet\UDB3\Event\Events\Moderation\Rejected;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\Commands\UpdateAvailableFrom;
use CultuurNet\UDB3\Offer\OfferRepository;
use CultuurNet\UDB3\Place\PlaceRepository;
use DateTimeImmutable;

final class UpdateAvailableFromHandlerTest extends CommandHandlerScenarioTestCase
{
    private EventStore $traceableEventStore;

    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): CommandHandler
    {
        $this->traceableEventStore = $eventStore;

        return new UpdateAvailableFromHandler(
            new OfferRepository(
                new EventRepository($eventStore, $eventBus),
                new PlaceRepository($eventStore, $eventBus)
            )
        );
    }

    /**
     * @dataProvider updateAvailableFromDataProvider
     * @test
     */
    public function it_handles_update_available_from(array $given, UpdateAvailableFrom $updateAvailableFrom, array $then): void
    {
        $this->scenario
            ->withAggregateId($updateAvailableFrom->getItemId())
            ->given($given)
            ->when($updateAvailableFrom)
            ->then($then);
    }

    public function updateAvailableFromDataProvider(): array
    {
        $eventId = '300ed979-03d8-48a0-859a-d03aeab0fa6a';

        $eventCreated = new EventCreated(
            $eventId,
            new Language('nl'),
            'Permanent Event',
            new EventType('0.50.4.0.0', 'concert'),
            new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
            new Calendar(CalendarType::PERMANENT())
        );

        return [
            'available from in future' => [
                [$eventCreated],
                new UpdateAvailableFrom($eventId, new DateTimeImmutable('2030-11-15T11:22:33+00:00')),
                [new AvailableFromUpdated($eventId, new DateTimeImmutable('2030-11-15T11:22:33+00:00'))],
            ],
            'available from on published event' => [
                [$eventCreated, new Published($eventId, new DateTimeImmutable('2020-11-15T11:22:33+00:00'))],
                new UpdateAvailableFrom($eventId, new DateTimeImmutable('2030-11-15T11:22:33+00:00')),
                [new AvailableFromUpdated($eventId, new DateTimeImmutable('2030-11-15T11:22:33+00:00'))],
            ],
            'available from on approved event' => [
                [$eventCreated, new Approved($eventId)],
                new UpdateAvailableFrom($eventId, new DateTimeImmutable('2030-11-15T11:22:33+00:00')),
                [new AvailableFromUpdated($eventId, new DateTimeImmutable('2030-11-15T11:22:33+00:00'))],
            ],
            'available from on rejected event' => [
                [$eventCreated, new Rejected($eventId, 'Rejected')],
                new UpdateAvailableFrom($eventId, new DateTimeImmutable('2030-11-15T11:22:33+00:00')),
                [new AvailableFromUpdated($eventId, new DateTimeImmutable('2030-11-15T11:22:33+00:00'))],
            ],
            'available from on deleted event' => [
                [$eventCreated, new EventDeleted($eventId)],
                new UpdateAvailableFrom($eventId, new DateTimeImmutable('2030-11-15T11:22:33+00:00')),
                [new AvailableFromUpdated($eventId, new DateTimeImmutable('2030-11-15T11:22:33+00:00'))],
            ],
            'the already set available from is the same' => [
                [$eventCreated, new AvailableFromUpdated($eventId, new DateTimeImmutable('2030-11-15T11:22:33+00:00'))],
                new UpdateAvailableFrom($eventId, new DateTimeImmutable('2030-11-15T11:22:33+00:00')),
                [],
            ],
            'the already set available from is different' => [
                [$eventCreated, new AvailableFromUpdated($eventId, new DateTimeImmutable('2030-11-15T11:22:33+00:00'))],
                new UpdateAvailableFrom($eventId, new DateTimeImmutable('2035-11-15T11:22:33+00:00')),
                [new AvailableFromUpdated($eventId, new DateTimeImmutable('2035-11-15T11:22:33+00:00'))],
            ],
        ];
    }

    /**
     * @test
     */
    public function it_modifies_available_from_in_the_past_to_now(): void
    {
        if (!$this->traceableEventStore instanceof TraceableEventStore) {
            return;
        }

        $eventId = '300ed979-03d8-48a0-859a-d03aeab0fa6a';

        $eventCreated = new EventCreated(
            $eventId,
            new Language('nl'),
            'Permanent Event',
            new EventType('0.50.4.0.0', 'concert'),
            new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
            new Calendar(CalendarType::PERMANENT())
        );

        $updateAvailableFrom = new UpdateAvailableFrom($eventId, new DateTimeImmutable('2010-11-15T11:22:33+00:00'));

        $this->scenario
            ->withAggregateId($eventId)
            ->given([$eventCreated])
            ->when($updateAvailableFrom);

        $events = $this->traceableEventStore->getEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(AvailableFromUpdated::class, $events[0]);

        /** @var AvailableFromUpdated $availableFromUpdated */
        $availableFromUpdated = $events[0];
        $this->assertEquals($eventId, $availableFromUpdated->getItemId());

        $nowTimestamp = (new DateTimeImmutable())->getTimestamp();
        $availableFromTimeStamp = $availableFromUpdated->getAvailableFrom()->getTimestamp();
        $this->assertTrue($nowTimestamp - $availableFromTimeStamp < 1);
    }
}
