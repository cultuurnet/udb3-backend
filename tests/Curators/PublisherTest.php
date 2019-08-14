<?php

namespace CultuurNet\UDB3\Curators;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PublisherTest extends TestCase
{
    /**
     * @test
     */
    public function it_will_throw_exception_on_invalid_publisher()
    {
        $this->expectException(InvalidArgumentException::class);
        PublisherName::fromName('');
    }
}
