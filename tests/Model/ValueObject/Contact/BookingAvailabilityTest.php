<?php

namespace CultuurNet\UDB3\Model\ValueObject\Contact;

use PHPUnit\Framework\TestCase;

class BookingAvailabilityTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_throw_an_exception_if_from_takes_place_after_to()
    {
        $from = \DateTimeImmutable::createFromFormat('d-m-Y', '18-01-2018');
        $to = \DateTimeImmutable::createFromFormat('d-m-Y', '01-01-2018');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"From" date should not be later than the "to" date.');

        new BookingAvailability($from, $to);
    }

    /**
     * @test
     */
    public function it_should_be_creatable_with_just_a_from_date()
    {
        $from = \DateTimeImmutable::createFromFormat('d-m-Y', '01-01-2018');
        $availability = BookingAvailability::from($from);
        $this->assertEquals($from, $availability->getFrom());
    }

    /**
     * @test
     */
    public function it_should_be_creatable_with_just_a_to_date()
    {
        $to = \DateTimeImmutable::createFromFormat('d-m-Y', '18-01-2018');
        $availability = BookingAvailability::to($to);
        $this->assertEquals($to, $availability->getTo());
    }

    /**
     * @test
     */
    public function it_should_be_creatable_with_both_a_from_and_to_date()
    {
        $from = \DateTimeImmutable::createFromFormat('d-m-Y', '01-01-2018');
        $to = \DateTimeImmutable::createFromFormat('d-m-Y', '18-01-2018');
        $availability = BookingAvailability::fromTo($from, $to);

        $this->assertEquals($from, $availability->getFrom());
        $this->assertEquals($to, $availability->getTo());
    }
}
