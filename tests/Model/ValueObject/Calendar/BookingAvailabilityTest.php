<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use PHPUnit\Framework\TestCase;

final class BookingAvailabilityTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_no_waiting_list_url_by_default(): void
    {
        $this->assertNull(BookingAvailability::Unavailable()->getWaitingListUrl());
    }

    /**
     * @test
     */
    public function it_can_set_a_waiting_list_url(): void
    {
        $url = new Url('https://publiq.be/wachtlijst');
        $bookingAvailability = BookingAvailability::Unavailable()->withWaitingListUrl($url);

        $this->assertEquals($url, $bookingAvailability->getWaitingListUrl());
    }

    /**
     * @test
     */
    public function it_can_clear_a_waiting_list_url(): void
    {
        $bookingAvailability = BookingAvailability::Unavailable()
            ->withWaitingListUrl(new Url('https://publiq.be/wachtlijst'))
            ->withWaitingListUrl(null);

        $this->assertNull($bookingAvailability->getWaitingListUrl());
    }

    /**
     * @test
     */
    public function it_returns_a_new_instance_when_changing_the_waiting_list_url(): void
    {
        $original = BookingAvailability::Unavailable();

        $this->assertNotSame($original, $original->withWaitingListUrl(new Url('https://publiq.be/wachtlijst')));
        $this->assertNull($original->getWaitingListUrl());
    }
}
