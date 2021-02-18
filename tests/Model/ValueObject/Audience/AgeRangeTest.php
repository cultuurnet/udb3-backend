<?php

namespace CultuurNet\UDB3\Model\ValueObject\Audience;

use PHPUnit\Framework\TestCase;

class AgeRangeTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_never_have_a_from_greater_than_the_to()
    {
        $from = new Age(10);
        $to = new Age(8);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"From" age should not be greater than the "to" age.');

        new AgeRange($from, $to);
    }

    /**
     * @test
     */
    public function it_should_return_the_given_from_and_to()
    {
        $from = new Age(10);
        $to = new Age(18);
        $range = new AgeRange($from, $to);

        $this->assertEquals($from, $range->getFrom());
        $this->assertEquals($to, $range->getTo());
    }

    /**
     * @test
     */
    public function it_should_be_creatable_with_just_a_from()
    {
        $from = new Age(10);
        $range = AgeRange::from($from);

        $this->assertEquals($from, $range->getFrom());
        $this->assertNull($range->getTo());
    }

    /**
     * @test
     */
    public function it_should_be_creatable_with_just_a_to()
    {
        $to = new Age(10);
        $range = AgeRange::to($to);

        $this->assertEquals(new Age(0), $range->getFrom());
        $this->assertEquals($to, $range->getTo());
    }

    /**
     * @test
     */
    public function it_should_be_creatable_with_from_and_to()
    {
        $from = new Age(10);
        $to = new Age(18);
        $range = AgeRange::fromTo($from, $to);

        $this->assertEquals($from, $range->getFrom());
        $this->assertEquals($to, $range->getTo());
    }

    /**
     * @test
     */
    public function it_should_be_creatable_with_exactly_one_age()
    {
        $age = new Age(10);
        $range = AgeRange::exactly($age);

        $this->assertEquals($age, $range->getFrom());
        $this->assertEquals($age, $range->getTo());
    }

    /**
     * @test
     */
    public function it_should_be_creatable_with_any_age()
    {
        $range = AgeRange::any();

        $this->assertNull($range->getFrom());
        $this->assertNull($range->getTo());
    }
}
