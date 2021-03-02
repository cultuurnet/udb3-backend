<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Collection\Behaviour;

class IsNotEmptyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_should_throw_an_exception_if_no_values_are_given_via_the_constructor()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Array should not be empty.');

        new MockNotEmptyCollection();
    }
}
