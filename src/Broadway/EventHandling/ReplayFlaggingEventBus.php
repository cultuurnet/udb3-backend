<?php

namespace CultuurNet\UDB3\Broadway\EventHandling;

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainEventStreamInterface;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventBusInterface;
use Broadway\EventHandling\EventListenerInterface;
use CultuurNet\Broadway\Domain\DomainMessageIsReplayed;

class ReplayFlaggingEventBus implements ReplayModeEventBusInterface
{
    /**
     * @var EventBusInterface
     */
    private $eventBus;

    /**
     * @var bool
     */
    private $replayMode;

    /**
     * @param EventBusInterface $eventBus
     */
    public function __construct(EventBusInterface $eventBus)
    {
        $this->eventBus = $eventBus;
        $this->replayMode = false;
    }

    public function startReplayMode()
    {
        $this->replayMode = true;
    }

    public function stopReplayMode()
    {
        $this->replayMode = false;
    }

    /**
     * @param DomainEventStreamInterface|DomainMessage[] $domainMessages
     */
    public function publish(DomainEventStreamInterface $domainMessages)
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

    /**
     * @param EventListenerInterface $eventListener
     */
    public function subscribe(EventListenerInterface $eventListener)
    {
        $this->eventBus->subscribe($eventListener);
    }
}
