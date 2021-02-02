<?php

namespace CultuurNet\UDB3\Model\ValueObject\String\Behaviour;

class IsStringTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_should_accept_and_return_a_string_value()
    {
        $stringValue = 'test';
        $vo = new MockString($stringValue);
        $this->assertEquals($stringValue, $vo->toString());
    }

    /**
     * @test
     */
    public function it_should_be_comparable_to_other_objects_of_the_same_type()
    {
        $lorem = new MockString('lorem');
        $ipsum = new MockString('ipsum');

        $loremOtherType = new MockNotEmptyString('lorem');

        $this->assertTrue($lorem->sameAs($lorem));
        $this->assertFalse($lorem->sameAs($ipsum));
        $this->assertFalse($lorem->sameAs($loremOtherType));
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_a_non_string_value_is_given()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Given value should be a string, got integer instead.');
        new MockString(123);
    }
}
