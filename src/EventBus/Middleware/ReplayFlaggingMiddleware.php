<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventBus\Middleware;

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Broadway\Domain\DomainMessageIsReplayed;
use CultuurNet\UDB3\EventBus\EventBusMiddleware;

final class ReplayFlaggingMiddleware implements EventBusMiddleware
{
    private static bool $replaying = false;

    /**
     * Static so that if multiple instances of the middleware are created accidentally, they are all in sync.
     */
    public static function startReplayMode(): void
    {
        self::$replaying = true;
    }

    /**
     * Static so that if multiple instances of the middleware are created accidentally, they are all in sync.
     */
    public static function stopReplayMode(): void
    {
        self::$replaying = false;
    }

    public function beforePublish(DomainEventStream $domainEventStream): DomainEventStream
    {
        $replayMetadata = new Metadata(
            [
                DomainMessageIsReplayed::METADATA_REPLAY_KEY => self::$replaying,
            ]
        );

        $messages = [];
        foreach ($domainEventStream as $domainMessage) {
            if ($domainMessage instanceof DomainMessage) {
                $domainMessage = $domainMessage->andMetadata($replayMetadata);
            }
            $messages[] = $domainMessage;
        }
        return new DomainEventStream($messages);
    }
}
