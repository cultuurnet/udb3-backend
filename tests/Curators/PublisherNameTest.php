<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Curators;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PublisherNameTest extends TestCase
{
    /**
     * @test
     */
    public function it_will_throw_exception_on_invalid_publisher()
    {
        $this->expectException(InvalidArgumentException::class);
        new PublisherName('');
    }

    /**
     * @test
     */
    public function it_is_case_insensitive(): void
    {
        $publisher = new PublisherName('Bruzz');
        $otherPublisher = new PublisherName('bruzz');
        $this->assertTrue($publisher->equals($otherPublisher));
    }
}
