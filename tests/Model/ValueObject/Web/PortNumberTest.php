<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Web;

use PHPUnit\Framework\TestCase;

final class PortNumberTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_accept_a_valid_portnumber(): void
    {
        $validPortnumber = 32768;
        $portNumber = new PortNumber($validPortnumber);
        $this->assertEquals($validPortnumber, $portNumber->toInteger());
    }

    /**
     * @test
     */
    public function it_should_reject_an_invalid_portnumber(): void
    {
        $invalidPortnumber = 65536;
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Port Number should be an integer between 0 and 65535.');

        new PortNumber($invalidPortnumber);
    }
}
