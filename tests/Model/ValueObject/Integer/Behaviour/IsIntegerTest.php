<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Integer\Behaviour;

class IsIntegerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_should_accept_and_return_an_integer_value()
    {
        $integerValue = 123;
        $vo = new MockInteger($integerValue);
        $this->assertEquals($integerValue, $vo->toInteger());
    }

    /**
     * @test
     */
    public function it_should_be_comparable_to_other_objects_of_the_same_type()
    {
        $one = new MockInteger(1);
        $two = new MockInteger(2);
        $three = new MockInteger(3);

        $this->assertTrue($one->sameAs($one));
        $this->assertTrue($two->sameAs($two));
        $this->assertFalse($one->sameAs($two));

        $this->assertTrue($one->equals($one));
        $this->assertFalse($one->equals($two));
        $this->assertFalse($one->equals($three));

        $this->assertFalse($one->lt($one));
        $this->assertTrue($one->lt($two));
        $this->assertTrue($one->lt($three));

        $this->assertFalse($two->lt($one));
        $this->assertFalse($two->lt($two));
        $this->assertTrue($two->lt($three));

        $this->assertFalse($three->lt($one));
        $this->assertFalse($three->lt($two));
        $this->assertFalse($three->lt($three));

        $this->assertTrue($one->lte($one));
        $this->assertTrue($one->lte($two));
        $this->assertTrue($one->lte($three));

        $this->assertFalse($two->lte($one));
        $this->assertTrue($two->lte($two));
        $this->assertTrue($two->lte($three));

        $this->assertFalse($three->lte($one));
        $this->assertFalse($three->lte($two));
        $this->assertTrue($three->lte($three));

        $this->assertFalse($one->gt($one));
        $this->assertFalse($one->gt($two));
        $this->assertFalse($one->gt($three));

        $this->assertTrue($two->gt($one));
        $this->assertFalse($two->gt($two));
        $this->assertFalse($two->gt($three));

        $this->assertTrue($three->gt($one));
        $this->assertTrue($three->gt($two));
        $this->assertFalse($three->gt($three));

        $this->assertTrue($one->gte($one));
        $this->assertFalse($one->gte($two));
        $this->assertFalse($one->gte($three));

        $this->assertTrue($two->gte($one));
        $this->assertTrue($two->gte($two));
        $this->assertFalse($two->gte($three));

        $this->assertTrue($three->gte($one));
        $this->assertTrue($three->gte($two));
        $this->assertTrue($three->gte($three));
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_a_non_integer_value_is_given()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Given value should be an int, got string instead.');
        new MockInteger('123');
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_when_checking_if_greater_or_less_than_an_invalid_object()
    {
        $integer = new MockInteger(123);
        $other = new DifferentMockInteger(456);

        $expectedClass = MockInteger::class;
        $otherClass = DifferentMockInteger::class;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Expected {$expectedClass}, got {$otherClass} instead.");

        $integer->lt($other);
    }
}
