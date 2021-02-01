<?php

namespace CultuurNet\UDB3\EventSourcing;

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainEventStreamInterface;
use Broadway\Domain\DomainMessage;

class CopyAwareEventStoreDecorator extends AbstractEventStoreDecorator
{
    /**
     * @inheritdoc
     */
    public function load($id)
    {
        return $this->loadCompleteStream(parent::load($id));
    }

    /**
     * @param DomainEventStreamInterface $eventStream
     * @return DomainEventStreamInterface
     */
    private function loadCompleteStream(DomainEventStreamInterface $eventStream)
    {
        $events = iterator_to_array($eventStream);
        /** @var DomainMessage $oldestMessage */
        $oldestMessage = current($events);
        if (intval($oldestMessage->getPlayhead()) === 0) {
            return $eventStream;
        }

        $parentId = $this->identifyParent($oldestMessage);
        $parentEventStream = parent::load($parentId);

        $inheritedEvents = $this->limitEventStreamToPlayhead($parentEventStream, $oldestMessage->getPlayhead());
        $combinedEvents = array_merge($inheritedEvents, $events);

        return $this->loadCompleteStream(new DomainEventStream($combinedEvents));
    }

    /**
     * @param DomainMessage $message
     * @return string
     *
     * @throws UnknownParentAggregateException
     */
    private function identifyParent(DomainMessage $message)
    {
        /** @var AggregateCopiedEventInterface $domainEvent */
        $domainEvent = $message->getPayload();

        if (!$domainEvent instanceof AggregateCopiedEventInterface) {
            throw new UnknownParentAggregateException();
        }

        return $domainEvent->getParentAggregateId();
    }

    /**
     * @param DomainEventStreamInterface $eventStream
     * @param int $playhead
     *
     * @return DomainMessage[]
     */
    private function limitEventStreamToPlayhead(DomainEventStreamInterface $eventStream, $playhead)
    {
        return array_filter(
            iterator_to_array($eventStream),
            function (DomainMessage $message) use ($playhead) {
                return intval($message->getPlayhead()) < $playhead;
            }
        );
    }
}
