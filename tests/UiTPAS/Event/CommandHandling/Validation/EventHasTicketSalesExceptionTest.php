<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPAS\Event\CommandHandling\Validation;

use PHPUnit\Framework\TestCase;

class EventHasTicketSalesExceptionTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_have_a_readable_error_message()
    {
        $eventId = 'b41c1a96-520e-4725-92a6-855657101f99';
        $expected = 'Event b41c1a96-520e-4725-92a6-855657101f99 has already had ticket sales in UiTPAS.';
        $exception = new EventHasTicketSalesException($eventId);
        $this->assertEquals($expected, $exception->getMessage());
    }
}
