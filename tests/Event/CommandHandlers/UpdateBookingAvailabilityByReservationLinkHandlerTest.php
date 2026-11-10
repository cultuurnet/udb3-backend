<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Event\Commands\UpdateBookingAvailabilityByReservationLink;
use CultuurNet\UDB3\Event\EventRepository;
use CultuurNet\UDB3\Event\Events\CalendarUpdated;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Calendar\DateRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SingleSubEventCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvent;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEventBookingInfo;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use DateTimeImmutable;

final class UpdateBookingAvailabilityByReservationLinkHandlerTest extends CommandHandlerScenarioTestCase
{
    private const EVENT_ID = '40021958-0ad8-46bd-8528-3ac3686818a1';
    private const RESERVATION_LINK = 'https://leuven-school-met-cultuur.ticketmatic.com/30cc/register/12345';
    private const USER_WAITING_LIST_URL = 'https://www.example.com/wachtlijst';
    private const PROVIDER_WAITING_LIST_URL = 'https://leuven-school-met-cultuur.ticketmatic.com/30cc/waitinglist/12345';

    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): CommandHandler
    {
        return new UpdateBookingAvailabilityByReservationLinkHandler(new EventRepository($eventStore, $eventBus));
    }

    /**
     * @test
     */
    public function it_updates_the_availability_of_the_dates_with_the_link(): void
    {
        $this->scenario
            ->withAggregateId(self::EVENT_ID)
            ->given([
                new EventCreated(
                    self::EVENT_ID,
                    new Language('nl'),
                    'Voorstelling',
                    new Category(new CategoryID('0.55.0.0.0'), new CategoryLabel('Theatervoorstelling'), CategoryDomain::eventType()),
                    new LocationId('322d67b6-e84d-4649-9384-12ecad74eab3'),
                    new SingleSubEventCalendar($this->getSubEvent())
                ),
            ])
            ->when(
                new UpdateBookingAvailabilityByReservationLink(
                    new Uuid(self::EVENT_ID),
                    new Url(self::RESERVATION_LINK),
                    BookingAvailability::Unavailable()
                )
            )
            ->then([
                new CalendarUpdated(
                    self::EVENT_ID,
                    new SingleSubEventCalendar(
                        $this->getSubEvent()->withBookingAvailability(BookingAvailability::Unavailable())
                    )
                ),
            ]);
    }

    /**
     * @test
     */
    public function it_keeps_a_waiting_list_url_entered_by_a_user_when_the_provider_has_none(): void
    {
        $subEvent = $this->getSubEvent()->withBookingAvailability(
            BookingAvailability::Unavailable()->withWaitingListUrl(new Url(self::USER_WAITING_LIST_URL))
        );

        $this->scenario
            ->withAggregateId(self::EVENT_ID)
            ->given([$this->getEventCreated($subEvent)])
            ->when(
                new UpdateBookingAvailabilityByReservationLink(
                    new Uuid(self::EVENT_ID),
                    new Url(self::RESERVATION_LINK),
                    BookingAvailability::Available()
                )
            )
            ->then([
                new CalendarUpdated(
                    self::EVENT_ID,
                    new SingleSubEventCalendar(
                        $subEvent->withBookingAvailability(
                            BookingAvailability::Available()->withWaitingListUrl(new Url(self::USER_WAITING_LIST_URL))
                        )
                    )
                ),
            ]);
    }

    /**
     * @test
     */
    public function it_clears_a_waiting_list_url_of_the_provider_when_the_provider_has_none(): void
    {
        $subEvent = $this->getSubEvent()->withBookingAvailability(
            BookingAvailability::Unavailable()->withWaitingListUrl(new Url(self::PROVIDER_WAITING_LIST_URL))
        );

        $this->scenario
            ->withAggregateId(self::EVENT_ID)
            ->given([$this->getEventCreated($subEvent)])
            ->when(
                new UpdateBookingAvailabilityByReservationLink(
                    new Uuid(self::EVENT_ID),
                    new Url(self::RESERVATION_LINK),
                    BookingAvailability::Available()
                )
            )
            ->then([
                new CalendarUpdated(
                    self::EVENT_ID,
                    new SingleSubEventCalendar($subEvent->withBookingAvailability(BookingAvailability::Available()))
                ),
            ]);
    }

    /**
     * @test
     */
    public function it_replaces_the_waiting_list_url_with_the_one_of_the_provider(): void
    {
        $subEvent = $this->getSubEvent()->withBookingAvailability(
            BookingAvailability::Unavailable()->withWaitingListUrl(new Url(self::USER_WAITING_LIST_URL))
        );
        $providerAvailability = BookingAvailability::Unavailable()
            ->withWaitingListUrl(new Url(self::PROVIDER_WAITING_LIST_URL));

        $this->scenario
            ->withAggregateId(self::EVENT_ID)
            ->given([$this->getEventCreated($subEvent)])
            ->when(
                new UpdateBookingAvailabilityByReservationLink(
                    new Uuid(self::EVENT_ID),
                    new Url(self::RESERVATION_LINK),
                    $providerAvailability
                )
            )
            ->then([
                new CalendarUpdated(
                    self::EVENT_ID,
                    new SingleSubEventCalendar($subEvent->withBookingAvailability($providerAvailability))
                ),
            ]);
    }

    private function getEventCreated(SubEvent $subEvent): EventCreated
    {
        return new EventCreated(
            self::EVENT_ID,
            new Language('nl'),
            'Voorstelling',
            new Category(new CategoryID('0.55.0.0.0'), new CategoryLabel('Theatervoorstelling'), CategoryDomain::eventType()),
            new LocationId('322d67b6-e84d-4649-9384-12ecad74eab3'),
            new SingleSubEventCalendar($subEvent)
        );
    }

    private function getSubEvent(): SubEvent
    {
        return SubEvent::createAvailable(
            new DateRange(
                new DateTimeImmutable('2026-11-05T10:00:00+01:00'),
                new DateTimeImmutable('2026-11-05T12:00:00+01:00')
            )
        )->withBookingInfo(new SubEventBookingInfo(new Url(self::RESERVATION_LINK)));
    }
}
