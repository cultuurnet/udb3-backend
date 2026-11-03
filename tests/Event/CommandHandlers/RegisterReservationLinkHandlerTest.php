<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Event\Commands\RegisterReservationLink;
use CultuurNet\UDB3\Event\EventRepository;
use CultuurNet\UDB3\Event\Events\CalendarUpdated;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\ReservationLinkRegistered;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Calendar\DateRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SingleSubEventCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvent;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEventBookingInfo;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Ticketing\Providers\InvalidReservationLink;
use CultuurNet\UDB3\Ticketing\Providers\ReservationLinkBookingInfo;
use CultuurNet\UDB3\Ticketing\Providers\ReservationLinkNotFound;
use CultuurNet\UDB3\Ticketing\Providers\TicketingProvider;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use RuntimeException;

final class RegisterReservationLinkHandlerTest extends CommandHandlerScenarioTestCase
{
    private const EVENT_ID = '40021958-0ad8-46bd-8528-3ac3686818a1';
    private const RESERVATION_LINK = 'https://leuven-school-met-cultuur.ticketmatic.com/30cc/register/12345';

    private TicketingProvider&MockObject $ticketingProvider;

    private LoggerInterface&MockObject $logger;

    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): CommandHandler
    {
        $this->ticketingProvider = $this->createMock(TicketingProvider::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        return new RegisterReservationLinkHandler(
            new EventRepository($eventStore, $eventBus),
            $this->ticketingProvider,
            $this->logger
        );
    }

    /**
     * @test
     */
    public function it_registers_the_link_with_what_the_provider_answers(): void
    {
        $this->ticketingProvider->method('getBookingInfo')
            ->with(new Url(self::RESERVATION_LINK))
            ->willReturn(
                new ReservationLinkBookingInfo('ticketmatic:30cc:12345', BookingAvailability::Available(), null)
            );

        $this->scenario
            ->withAggregateId(self::EVENT_ID)
            ->given([$this->getEventCreated()])
            ->when(new RegisterReservationLink(self::EVENT_ID, new Url(self::RESERVATION_LINK)))
            ->then([
                new ReservationLinkRegistered(self::EVENT_ID, new Url(self::RESERVATION_LINK), 'ticketmatic:30cc:12345'),
            ]);
    }

    /**
     * @test
     */
    public function it_sets_the_waiting_list_url_of_the_provider_on_the_dates_with_the_link(): void
    {
        $this->ticketingProvider->method('getBookingInfo')
            ->with(new Url(self::RESERVATION_LINK))
            ->willReturn(
                new ReservationLinkBookingInfo(
                    'ticketmatic:30cc:12345',
                    BookingAvailability::Unavailable(),
                    new Url('https://www.example.com/waiting-list')
                )
            );

        $this->scenario
            ->withAggregateId(self::EVENT_ID)
            ->given([$this->getEventCreated()])
            ->when(new RegisterReservationLink(self::EVENT_ID, new Url(self::RESERVATION_LINK)))
            ->then([
                new ReservationLinkRegistered(self::EVENT_ID, new Url(self::RESERVATION_LINK), 'ticketmatic:30cc:12345'),
                new CalendarUpdated(
                    self::EVENT_ID,
                    new SingleSubEventCalendar(
                        $this->getSubEvent()->withBookingAvailability(
                            BookingAvailability::Unavailable()->withWaitingListUrl(new Url('https://www.example.com/waiting-list'))
                        )
                    )
                ),
            ]);
    }

    /**
     * @test
     */
    public function it_does_nothing_when_the_provider_has_no_open_event_behind_the_link(): void
    {
        $this->ticketingProvider->method('getBookingInfo')
            ->willThrowException(ReservationLinkNotFound::forLink(new Url(self::RESERVATION_LINK)));
        $this->logger->expects($this->once())->method('info');

        $this->scenario
            ->withAggregateId(self::EVENT_ID)
            ->given([$this->getEventCreated()])
            ->when(new RegisterReservationLink(self::EVENT_ID, new Url(self::RESERVATION_LINK)))
            ->then([]);
    }

    /**
     * @test
     */
    public function it_does_nothing_when_the_provider_rejects_the_link(): void
    {
        $this->ticketingProvider->method('getBookingInfo')
            ->willThrowException(InvalidReservationLink::forLink(new Url(self::RESERVATION_LINK)));
        $this->logger->expects($this->once())->method('warning');

        $this->scenario
            ->withAggregateId(self::EVENT_ID)
            ->given([$this->getEventCreated()])
            ->when(new RegisterReservationLink(self::EVENT_ID, new Url(self::RESERVATION_LINK)))
            ->then([]);
    }

    /**
     * @test
     */
    public function it_logs_and_does_not_fail_when_the_provider_is_unavailable(): void
    {
        $this->ticketingProvider->method('getBookingInfo')
            ->willThrowException(new RuntimeException('Ticketmatic API returned status code 500'));
        $this->logger->expects($this->once())->method('error');

        $this->scenario
            ->withAggregateId(self::EVENT_ID)
            ->given([$this->getEventCreated()])
            ->when(new RegisterReservationLink(self::EVENT_ID, new Url(self::RESERVATION_LINK)))
            ->then([]);
    }

    private function getEventCreated(): EventCreated
    {
        return new EventCreated(
            self::EVENT_ID,
            new Language('nl'),
            'Voorstelling',
            new Category(new CategoryID('0.55.0.0.0'), new CategoryLabel('Theatervoorstelling'), CategoryDomain::eventType()),
            new LocationId('322d67b6-e84d-4649-9384-12ecad74eab3'),
            new SingleSubEventCalendar($this->getSubEvent())
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
