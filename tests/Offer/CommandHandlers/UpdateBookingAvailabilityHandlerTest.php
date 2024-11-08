<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Calendar\CalendarType;
use CultuurNet\UDB3\Event\EventRepository;
use CultuurNet\UDB3\Event\Events\CalendarUpdated;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Offer\Commands\UpdateBookingAvailability;
use CultuurNet\UDB3\Offer\OfferRepository;
use CultuurNet\UDB3\Offer\CalendarTypeNotSupported;
use CultuurNet\UDB3\Offer\ValueObjects\BookingAvailability as LegacyBookingAvailability;
use CultuurNet\UDB3\Place\PlaceRepository;
use CultuurNet\UDB3\Calendar\Timestamp;
use DateTime;

final class UpdateBookingAvailabilityHandlerTest extends CommandHandlerScenarioTestCase
{
    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): CommandHandler
    {
        return new UpdateBookingAvailabilityHandler(
            new OfferRepository(
                new EventRepository($eventStore, $eventBus),
                new PlaceRepository($eventStore, $eventBus)
            )
        );
    }

    /**
     * @test
     */
    public function it_does_not_update_offers_with_permanent_calendar(): void
    {
        $permanentEventCreated = new EventCreated(
            '1',
            new Language('nl'),
            'Permanent Event',
            new EventType('0.50.4.0.0', 'concert'),
            new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
            new Calendar(CalendarType::PERMANENT())
        );

        $this->expectException(CalendarTypeNotSupported::class);

        $this->scenario
            ->withAggregateId('1')
            ->given([$permanentEventCreated])
            ->when(new UpdateBookingAvailability('1', LegacyBookingAvailability::unavailable()))
            ->then([]);
    }

    /**
     * @test
     */
    public function it_does_not_update_offers_with_periodic_calendar(): void
    {
        $periodicEventCreated = new EventCreated(
            '1',
            new Language('nl'),
            'Periodic Event',
            new EventType('0.50.4.0.0', 'concert'),
            new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
            new Calendar(
                CalendarType::PERIODIC(),
                new DateTime('2020-01-01 10:00:00'),
                new DateTime('2020-01-01 12:00:00')
            )
        );

        $this->expectException(CalendarTypeNotSupported::class);

        $this->scenario
            ->withAggregateId('1')
            ->given([$periodicEventCreated])
            ->when(new UpdateBookingAvailability('1', LegacyBookingAvailability::unavailable()))
            ->then([]);
    }

    /**
     * @test
     */
    public function it_update_offers_with_single_calendar(): void
    {
        $singleEventCreated = new EventCreated(
            '1',
            new Language('nl'),
            'Single Event',
            new EventType('0.50.4.0.0', 'concert'),
            new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
            new Calendar(
                CalendarType::SINGLE(),
                null,
                null,
                [
                    new Timestamp(
                        new DateTime('2020-01-01 10:00:00'),
                        new DateTime('2020-01-01 12:00:00')
                    ),
                ]
            )
        );

        $updateBookingAvailability = new UpdateBookingAvailability('1', LegacyBookingAvailability::unavailable());

        $this->scenario
            ->withAggregateId('1')
            ->given([$singleEventCreated])
            ->when($updateBookingAvailability)
            ->then([
                new CalendarUpdated(
                    '1',
                    (new Calendar(
                        CalendarType::SINGLE(),
                        null,
                        null,
                        [
                            (new Timestamp(
                                new DateTime('2020-01-01 10:00:00'),
                                new DateTime('2020-01-01 12:00:00')
                            ))->withBookingAvailability(BookingAvailability::Unavailable()),
                        ]
                    ))->withBookingAvailability(BookingAvailability::Unavailable())
                ),
            ]);
    }

    /**
     * @test
     */
    public function it_update_offers_with_multiple_calendar(): void
    {
        $multipleEventCreated = new EventCreated(
            '1',
            new Language('nl'),
            'Multiple Event',
            new EventType('0.50.4.0.0', 'concert'),
            new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
            new Calendar(
                CalendarType::MULTIPLE(),
                null,
                null,
                [
                    new Timestamp(
                        new DateTime('2020-01-01 10:00:00'),
                        new DateTime('2020-01-01 12:00:00')
                    ),
                    new Timestamp(
                        new DateTime('2020-01-03 10:00:00'),
                        new DateTime('2020-01-03 12:00:00')
                    ),
                ]
            )
        );

        $updateBookingAvailability = new UpdateBookingAvailability('1', LegacyBookingAvailability::unavailable());

        $this->scenario
            ->withAggregateId('1')
            ->given([$multipleEventCreated])
            ->when($updateBookingAvailability)
            ->then([
                new CalendarUpdated(
                    '1',
                    (new Calendar(
                        CalendarType::MULTIPLE(),
                        null,
                        null,
                        [
                            (new Timestamp(
                                new DateTime('2020-01-01 10:00:00'),
                                new DateTime('2020-01-01 12:00:00')
                            ))->withBookingAvailability(BookingAvailability::Unavailable()),
                            (new Timestamp(
                                new DateTime('2020-01-03 10:00:00'),
                                new DateTime('2020-01-03 12:00:00')
                            ))->withBookingAvailability(BookingAvailability::Unavailable()),
                        ]
                    ))->withBookingAvailability(BookingAvailability::Unavailable())
                ),
            ]);
    }
}
