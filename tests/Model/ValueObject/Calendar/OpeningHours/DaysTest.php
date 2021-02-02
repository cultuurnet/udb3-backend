<?php

namespace CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours;

use PHPUnit\Framework\TestCase;

class DaysTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_have_unique_values()
    {
        $this->expectException(\InvalidArgumentException::class);

        new Days(
            Day::monday(),
            Day::tuesday(),
            Day::monday()
        );
    }
}
