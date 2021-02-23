<?php

namespace CultuurNet\UDB3\Broadway\EventHandling;

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventBus;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Broadway\Domain\DomainMessageIsReplayed;

class ReplayFlaggingEventBus implements ReplayModeEventBusInterface
{
    /**
     * @var EventBus
     */
    private $eventBus;

    /**
     * @var bool
     */
    private $replayMode;

    public function __construct(EventBus $eventBus)
    {
        $this->eventBus = $eventBus;
        $this->replayMode = false;
    }

    public function startReplayMode(): void
    {
        $this->replayMode = true;
    }

    public function stopReplayMode(): void
    {
        $this->replayMode = false;
    }

    /**
     * @param DomainEventStream|DomainMessage[] $domainMessages
     */
    public function publish(DomainEventStream $domainMessages): void
    {
        $replayMetadata = new Metadata(
            [
                DomainMessageIsReplayed::METADATA_REPLAY_KEY => $this->replayMode,
            ]
        );

        $messages = [];

        foreach ($domainMessages as $index => $domainMessage) {
            $messages[$index] = $domainMessage->andMetadata($replayMetadata);
        }

        $stream = new DomainEventStream($messages);

        $this->eventBus->publish($stream);
    }

    public function subscribe(EventListener $eventListener): void
    {
        $this->eventBus->subscribe($eventListener);
    }
}
