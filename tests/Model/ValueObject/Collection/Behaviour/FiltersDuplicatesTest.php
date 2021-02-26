<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Collection\Behaviour;

use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\MockString;

class FiltersDuplicatesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_should_throw_an_exception_if_duplicate_values_are_given_via_the_constructor()
    {
        $values = [
            new MockString('foo'),
            new MockString('bar'),
            new MockString('lorem'),
            new MockString('bar'),
            new MockString('ipsum'),
            new MockString('foo'),
        ];

        $expected = [
            new MockString('foo'),
            new MockString('bar'),
            new MockString('lorem'),
            new MockString('ipsum'),
        ];

        $collection = new MockFiltersDuplicatesCollection(...$values);
        $actual = $collection->toArray();

        $this->assertEquals($expected, $actual);
    }
}
