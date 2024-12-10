<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventBus\Middleware;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use PHPUnit\Framework\TestCase;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

class InterceptingMiddlewareTest extends TestCase
{
    private InterceptingMiddleware $interceptingMiddleware;

    protected function setUp(): void
    {
        $this->interceptingMiddleware = new InterceptingMiddleware();
    }

    /**
     * @test
     */
    public function it_intercepts_messages_with_a_payload_that_matches_the_callback_and_returns_unique_ones(): void
    {
        $callback = static fn (DomainMessage $message): bool => (bool) ($message->getPayload()->intercept ?? false);

        $createDomainMessage = static function (int $id, bool $intercept) {
            return new DomainMessage(
                UUID::uuid4()->toString(),
                0,
                new Metadata(),
                (object) ['id' => $id, 'intercept' => $intercept],
                DateTime::now()
            );
        };

        $domainMessage1 = $createDomainMessage(1, true);
        $domainMessage2 = $createDomainMessage(2, false);
        $domainMessage3 = $createDomainMessage(3, true);
        $stream1 = new DomainEventStream([$domainMessage1, $domainMessage2, $domainMessage3]);

        $domainMessage4 = $createDomainMessage(4, true);
        $domainMessage5 = $createDomainMessage(1, true);
        $domainMessage6 = $createDomainMessage(6, false);
        $stream2 = new DomainEventStream([$domainMessage4, $domainMessage5, $domainMessage6]);

        InterceptingMiddleware::startIntercepting($callback);
        $published1 = $this->interceptingMiddleware->beforePublish($stream1)->getIterator()->getArrayCopy();
        $published2 = $this->interceptingMiddleware->beforePublish($stream2)->getIterator()->getArrayCopy();
        InterceptingMiddleware::stopIntercepting();
        $published3 = $this->interceptingMiddleware->beforePublish($stream1)->getIterator()->getArrayCopy();

        // Note that $domainMessage5 should also be intercepted but not returned because it has the same payload as
        // $domainMessage1
        $expectedIntercepted = [
            $domainMessage1,
            $domainMessage3,
            $domainMessage4,
        ];
        $actualIntercepted = InterceptingMiddleware::getInterceptedMessagesWithUniquePayload()
            ->getIterator()
            ->getArrayCopy();

        $expectedPublished1 = [$domainMessage2];
        $expectedPublished2 = [$domainMessage6];
        $expectedPublished3 = [$domainMessage1, $domainMessage2, $domainMessage3];

        $this->assertEquals($expectedIntercepted, $actualIntercepted);
        $this->assertEquals($expectedPublished1, $published1);
        $this->assertEquals($expectedPublished2, $published2);
        $this->assertEquals($expectedPublished3, $published3);
    }
}
