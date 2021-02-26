<?php

namespace CultuurNet\UDB3\Model\ValueObject\Integer\Behaviour;

use PHPUnit\Framework\TestCase;

class IsNaturalTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_throw_an_exception_if_a_value_lower_than_zero_is_given()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Given integer should be greater or equal to zero. Got -1 instead.');

        new MockNatural(-1);
    }
}
