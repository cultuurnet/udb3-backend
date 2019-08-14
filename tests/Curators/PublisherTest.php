<?php

namespace CultuurNet\UDB3\Curators;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PublisherTest extends TestCase
{
    /**
     * @test
     */
    public function it_will_throw_exception_on_unknown_publisher()
    {
        $this->expectException(InvalidArgumentException::class);
        Publisher::fromName('DEFINITELY_NOT_A_PUBLISHER');
    }

    /**
     * @test
     */
    public function it_can_instantiate_known_publishers()
    {
        $expected = Publisher::bruzz();
        $publisher = Publisher::fromName('bruzz');

        $this->assertEquals($expected, $publisher);
    }
}
