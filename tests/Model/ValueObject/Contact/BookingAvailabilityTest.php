<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Contact;

use CultuurNet\UDB3\DateTimeFactory;
use PHPUnit\Framework\TestCase;

class BookingAvailabilityTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_throw_an_exception_if_from_takes_place_after_to(): void
    {
        $from = DateTimeFactory::fromFormat('d-m-Y', '18-01-2018');
        $to = DateTimeFactory::fromFormat('d-m-Y', '01-01-2018');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"From" date should not be later than the "to" date.');

        new BookingAvailability($from, $to);
    }

    /**
     * @test
     */
    public function it_should_be_creatable_with_just_a_from_date(): void
    {
        $from = DateTimeFactory::fromFormat('d-m-Y', '01-01-2018');
        $availability = BookingAvailability::from($from);
        $this->assertEquals($from, $availability->getFrom());
    }

    /**
     * @test
     */
    public function it_should_be_creatable_with_just_a_to_date(): void
    {
        $to = DateTimeFactory::fromFormat('d-m-Y', '18-01-2018');
        $availability = BookingAvailability::to($to);
        $this->assertEquals($to, $availability->getTo());
    }

    /**
     * @test
     */
    public function it_should_be_creatable_with_both_a_from_and_to_date(): void
    {
        $from = DateTimeFactory::fromFormat('d-m-Y', '01-01-2018');
        $to = DateTimeFactory::fromFormat('d-m-Y', '18-01-2018');
        $availability = BookingAvailability::fromTo($from, $to);

        $this->assertEquals($from, $availability->getFrom());
        $this->assertEquals($to, $availability->getTo());
    }
}
