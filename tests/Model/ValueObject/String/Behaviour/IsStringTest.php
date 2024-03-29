<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\String\Behaviour;

class IsStringTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_should_accept_and_return_a_string_value(): void
    {
        $stringValue = 'test';
        $vo = new MockString($stringValue);
        $this->assertEquals($stringValue, $vo->toString());
    }

    /**
     * @test
     */
    public function it_should_be_comparable_to_other_objects_of_the_same_type(): void
    {
        $lorem = new MockString('lorem');
        $ipsum = new MockString('ipsum');

        $loremOtherType = new MockNotEmptyString('lorem');

        $this->assertTrue($lorem->sameAs($lorem));
        $this->assertFalse($lorem->sameAs($ipsum));
        $this->assertFalse($lorem->sameAs($loremOtherType));
    }
}
