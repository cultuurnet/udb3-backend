<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use PHPUnit\Framework\TestCase;

class StatusTypeTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_have_exactly_three_values(): void
    {
        $available = StatusType::Available();
        $unavailable = StatusType::Unavailable();
        $temporarilyUnavailable = StatusType::TemporarilyUnavailable();

        $this->assertEquals('Available', $available->toString());
        $this->assertEquals('Unavailable', $unavailable->toString());
        $this->assertEquals('TemporarilyUnavailable', $temporarilyUnavailable->toString());
    }
}
