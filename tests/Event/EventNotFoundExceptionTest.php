<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use PHPUnit\Framework\TestCase;

class EventNotFoundExceptionTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_the_HTTP_NOT_FOUND_status_code_by_default(): void
    {
        $exception = new EventNotFoundException();

        $this->assertEquals(404, $exception->getCode());
    }
}
