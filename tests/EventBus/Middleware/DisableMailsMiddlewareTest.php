<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventBus\Middleware;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use PHPUnit\Framework\TestCase;

final class DisableMailsMiddlewareTest extends TestCase
{
    private DisableMailsMiddleware $disableMailsMiddleware;

    protected function setUp(): void
    {
        $this->disableMailsMiddleware = new DisableMailsMiddleware();
    }

    /**
     * @test
     */
    public function it_can_enable_and_disable_mails(): void
    {
        $stream1 = new DomainEventStream([$this->createDomainMessage(1)]);
        $stream2 = new DomainEventStream([$this->createDomainMessage(2)]);
        $stream3 = new DomainEventStream([$this->createDomainMessage(3)]);

        $expectedReplayMetadata = [
            false,
            true,
            false,
        ];

        $processedStreams = [];

        $processedStreams[] = $this->disableMailsMiddleware->beforePublish($stream1);
        DisableMailsMiddleware::disableMails();
        $processedStreams[] = $this->disableMailsMiddleware->beforePublish($stream2);
        DisableMailsMiddleware::enableMails();
        $processedStreams[] = $this->disableMailsMiddleware->beforePublish($stream3);

        $actualReplayMetadata = [];
        foreach ($processedStreams as $stream) {
            /** @var DomainMessage $domainMessage */
            foreach ($stream as $domainMessage) {
                $metadata = $domainMessage->getMetadata();
                $actualReplayMetadata[] = $metadata->get('disable_mails');
            }
        }

        $this->assertEquals($expectedReplayMetadata, $actualReplayMetadata);
    }

    private function createDomainMessage(int $id): DomainMessage
    {
        return new DomainMessage(
            Uuid::uuid4()->toString(),
            0,
            new Metadata(),
            (object) ['id' => $id],
            DateTime::now()
        );
    }
}
