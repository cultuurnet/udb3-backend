<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventBus\Middleware;

use Broadway\Domain\DomainEventStream;
use PHPUnit\Framework\TestCase;

class CallbackOnFirstPublicationMiddlewareTest extends TestCase
{
    /**
     * @test
     */
    public function it_calls_a_given_closure_once_before_the_first_publication(): void
    {
        $callbackCount = 0;
        $middleware = new CallbackOnFirstPublicationMiddleware(
            function () use (&$callbackCount): void {
                $callbackCount++;
            }
        );

        $middleware->beforePublish(new DomainEventStream([]));
        $middleware->beforePublish(new DomainEventStream([]));
        $middleware->beforePublish(new DomainEventStream([]));

        $this->assertEquals(1, $callbackCount);
    }
}
