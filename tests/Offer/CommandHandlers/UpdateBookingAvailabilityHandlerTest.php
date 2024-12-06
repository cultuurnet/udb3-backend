<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Event\EventRepository;
use CultuurNet\UDB3\Event\Events\CalendarUpdated;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\DateRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\MultipleSubEventsCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PeriodicCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PermanentCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SingleSubEventCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvent;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvents;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Offer\Commands\UpdateBookingAvailability;
use CultuurNet\UDB3\Offer\OfferRepository;
use CultuurNet\UDB3\Offer\CalendarTypeNotSupported;
use CultuurNet\UDB3\Place\PlaceRepository;
use DateTimeImmutable;

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
            new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType()),
            new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
            new PermanentCalendar(new OpeningHours())
        );

        $this->expectException(CalendarTypeNotSupported::class);

        $this->scenario
            ->withAggregateId('1')
            ->given([$permanentEventCreated])
            ->when(new UpdateBookingAvailability('1', BookingAvailability::Unavailable()))
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
            new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType()),
            new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
            new PeriodicCalendar(
                new DateRange(
                    new DateTimeImmutable('2020-01-01 10:00:00'),
                    new DateTimeImmutable('2020-01-01 12:00:00')
                ),
                new OpeningHours()
            )
        );

        $this->expectException(CalendarTypeNotSupported::class);

        $this->scenario
            ->withAggregateId('1')
            ->given([$periodicEventCreated])
            ->when(new UpdateBookingAvailability('1', BookingAvailability::Unavailable()))
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
            new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType()),
            new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
            new SingleSubEventCalendar(
                SubEvent::createAvailable(
                    new DateRange(
                        new DateTimeImmutable('2020-01-01 10:00:00'),
                        new DateTimeImmutable('2020-01-01 12:00:00')
                    )
                )
            )
        );

        $updateBookingAvailability = new UpdateBookingAvailability('1', BookingAvailability::Unavailable());

        $this->scenario
            ->withAggregateId('1')
            ->given([$singleEventCreated])
            ->when($updateBookingAvailability)
            ->then([
                new CalendarUpdated(
                    '1',
                    (new Calendar(
                        CalendarType::single(),
                        null,
                        null,
                        [
                            (SubEvent::createAvailable(
                                new DateRange(
                                    new DateTimeImmutable('2020-01-01 10:00:00'),
                                    new DateTimeImmutable('2020-01-01 12:00:00')
                                )
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
            new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType()),
            new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
            new MultipleSubEventsCalendar(
                new SubEvents(
                    SubEvent::createAvailable(
                        new DateRange(
                            new DateTimeImmutable('2020-01-01 10:00:00'),
                            new DateTimeImmutable('2020-01-01 12:00:00')
                        )
                    ),
                    SubEvent::createAvailable(
                        new DateRange(
                            new DateTimeImmutable('2020-01-03 10:00:00'),
                            new DateTimeImmutable('2020-01-03 12:00:00')
                        )
                    )
                )
            )
        );

        $updateBookingAvailability = new UpdateBookingAvailability('1', BookingAvailability::Unavailable());

        $this->scenario
            ->withAggregateId('1')
            ->given([$multipleEventCreated])
            ->when($updateBookingAvailability)
            ->then([
                new CalendarUpdated(
                    '1',
                    (new Calendar(
                        CalendarType::multiple(),
                        null,
                        null,
                        [
                            (SubEvent::createAvailable(
                                new DateRange(
                                    new DateTimeImmutable('2020-01-01 10:00:00'),
                                    new DateTimeImmutable('2020-01-01 12:00:00')
                                )
                            ))->withBookingAvailability(BookingAvailability::Unavailable()),
                            (SubEvent::createAvailable(
                                new DateRange(
                                    new DateTimeImmutable('2020-01-03 10:00:00'),
                                    new DateTimeImmutable('2020-01-03 12:00:00')
                                )
                            ))->withBookingAvailability(BookingAvailability::Unavailable()),
                        ]
                    ))->withBookingAvailability(BookingAvailability::Unavailable())
                ),
            ]);
    }
}
