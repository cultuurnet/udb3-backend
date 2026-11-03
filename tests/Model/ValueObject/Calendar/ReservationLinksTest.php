<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ReservationLinksTest extends TestCase
{
    private const LINK_A = 'https://leuven-school-met-cultuur.ticketmatic.com/30cc/register/1001';
    private const LINK_B = 'https://leuven-school-met-cultuur.ticketmatic.com/30cc/register/1002';
    private const LINK_C = 'https://leuven-school-met-cultuur.ticketmatic.com/30cc/register/1003';

    /**
     * @test
     */
    public function it_takes_each_link_of_the_sub_events_once(): void
    {
        $calendar = new MultipleSubEventsCalendar(
            new SubEvents(
                $this->subEvent('2026-11-05', self::LINK_A),
                $this->subEvent('2026-11-06', null),
                $this->subEvent('2026-11-07', self::LINK_B),
                $this->subEvent('2026-11-08', self::LINK_A)
            )
        );

        $this->assertEquals(
            new ReservationLinks(new Url(self::LINK_A), new Url(self::LINK_B)),
            ReservationLinks::fromCalendar($calendar)
        );
    }

    /**
     * @test
     */
    public function it_has_no_links_for_a_calendar_without_sub_events(): void
    {
        $this->assertEquals(new ReservationLinks(), ReservationLinks::fromCalendar(new PermanentCalendar(new OpeningHours())));
    }

    /**
     * @test
     */
    public function it_has_no_links_without_a_calendar(): void
    {
        $this->assertEquals(new ReservationLinks(), ReservationLinks::fromCalendar(null));
    }

    /**
     * @test
     */
    public function it_returns_the_links_that_are_not_in_the_other_collection(): void
    {
        $before = new ReservationLinks(new Url(self::LINK_A), new Url(self::LINK_B));
        $after = new ReservationLinks(new Url(self::LINK_B), new Url(self::LINK_C));

        $this->assertEquals([new Url(self::LINK_A)], array_values($before->notIn($after)->toArray()));
        $this->assertEquals([new Url(self::LINK_C)], array_values($after->notIn($before)->toArray()));
    }

    private function subEvent(string $date, ?string $link): SubEvent
    {
        $subEvent = SubEvent::createAvailable(
            new DateRange(
                new DateTimeImmutable($date . 'T10:00:00+01:00'),
                new DateTimeImmutable($date . 'T12:00:00+01:00')
            )
        );

        return $link === null ? $subEvent : $subEvent->withBookingInfo(new SubEventBookingInfo(new Url($link)));
    }
}
