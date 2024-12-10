<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventBus\Middleware;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use PHPUnit\Framework\TestCase;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

final class ReplayFlaggingMiddlewareTest extends TestCase
{
    private ReplayFlaggingMiddleware $replayFlaggingMiddleware;

    protected function setUp(): void
    {
        $this->replayFlaggingMiddleware = new ReplayFlaggingMiddleware();
    }

    /**
     * @test
     */
    public function it_adds_a_boolean_replay_key_to_the_metadata_of_every_message(): void
    {
        $createDomainMessage = static function (int $id) {
            return new DomainMessage(
                UUID::uuid4()->toString(),
                0,
                new Metadata(),
                (object) ['id' => $id],
                DateTime::now()
            );
        };

        $domainMessage1 = $createDomainMessage(1);
        $domainMessage2 = $createDomainMessage(2);
        $stream1 = new DomainEventStream([$domainMessage1, $domainMessage2]);

        $domainMessage3 = $createDomainMessage(3);
        $domainMessage4 = $createDomainMessage(4);
        $stream2 = new DomainEventStream([$domainMessage3, $domainMessage4]);

        $domainMessage5 = $createDomainMessage(5);
        $domainMessage6 = $createDomainMessage(6);
        $stream3 = new DomainEventStream([$domainMessage5, $domainMessage6]);

        $expectedReplayMetadata = [
            false,
            false,
            true,
            true,
            false,
            false,
        ];

        $processedStream1 = $this->replayFlaggingMiddleware->beforePublish($stream1);
        ReplayFlaggingMiddleware::startReplayMode();
        $processedStream2 = $this->replayFlaggingMiddleware->beforePublish($stream2);
        ReplayFlaggingMiddleware::stopReplayMode();
        $processedStream3 = $this->replayFlaggingMiddleware->beforePublish($stream3);

        $actualReplayMetadata = [];
        foreach ([$processedStream1, $processedStream2, $processedStream3] as $stream) {
            foreach ($stream as $domainMessage) {
                /** @var DomainMessage $domainMessage */
                $metadata = $domainMessage->getMetadata();
                $actualReplayMetadata[] = $metadata->get('replayed');
            }
        }

        $this->assertEquals($expectedReplayMetadata, $actualReplayMetadata);
    }
}
