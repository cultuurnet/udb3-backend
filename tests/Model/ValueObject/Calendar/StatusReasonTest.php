<?php

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use PHPUnit\Framework\TestCase;

class StatusReasonTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_not_be_empty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Given string should not be empty.');

        new StatusReason('');
    }

    /**
     * @test
     */
    public function it_should_return_the_original_string(): void
    {
        $string = 'test foo bar';
        $reason = new StatusReason($string);
        $this->assertEquals($string, $reason->toString());
    }
}
