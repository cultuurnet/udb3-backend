<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventBus;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\SimpleEventBus;
use Broadway\EventHandling\TraceableEventBus;
use PHPUnit\Framework\TestCase;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

class MiddlewareEventBusTest extends TestCase
{
    private TraceableEventBus $traceableEventBus;
    private MiddlewareEventBus $middlewareEventBus;

    protected function setUp(): void
    {
        $this->traceableEventBus = new TraceableEventBus(new SimpleEventBus());
        $this->middlewareEventBus = new MiddlewareEventBus($this->traceableEventBus);
    }

    /**
     * @test
     */
    public function it_calls_every_middleware_in_the_order_that_it_was_registered_before_a_publish(): void
    {
        // Test that the middlewares are called in the correct order by having each middleware append a specific
        // domain message to the domain event stream, and then check that the domain messages were added in the correct
        // order.
        $domainMessage1 = new DomainMessage(
            UUID::uuid4()->toString(),
            0,
            new Metadata(),
            (object) ['id' => 1],
            DateTime::now()
        );
        $middleware1 = $this->createMock(EventBusMiddleware::class);
        $middleware1->expects($this->exactly(2))
            ->method('beforePublish')
            ->willReturnCallback(
                function (DomainEventStream $domainEventStream) use ($domainMessage1): DomainEventStream {
                    $messages = $domainEventStream->getIterator()->getArrayCopy();
                    $messages[] = $domainMessage1;
                    return new DomainEventStream($messages);
                }
            );

        $domainMessage2 = new DomainMessage(
            UUID::uuid4()->toString(),
            0,
            new Metadata(),
            (object) ['id' => 2],
            DateTime::now()
        );
        $middleware2 = $this->createMock(EventBusMiddleware::class);
        $middleware2->expects($this->exactly(2))
            ->method('beforePublish')
            ->willReturnCallback(
                function (DomainEventStream $domainEventStream) use ($domainMessage2): DomainEventStream {
                    $messages = $domainEventStream->getIterator()->getArrayCopy();
                    $messages[] = $domainMessage2;
                    return new DomainEventStream($messages);
                }
            );

        $this->middlewareEventBus->registerMiddleware($middleware1);
        $this->middlewareEventBus->registerMiddleware($middleware2);

        $this->traceableEventBus->trace();

        // Do 2 publications, so we can check that the middlewares are called every time.
        $this->middlewareEventBus->publish(new DomainEventStream([]));
        $this->middlewareEventBus->publish(new DomainEventStream([]));

        $expectedPayloads = [
            (object) ['id' => 1],
            (object) ['id' => 2],
            (object) ['id' => 1],
            (object) ['id' => 2],
        ];

        $this->assertEquals($expectedPayloads, $this->traceableEventBus->getEvents());
    }
}
