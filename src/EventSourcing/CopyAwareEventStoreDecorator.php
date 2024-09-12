<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventSourcing;

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;

class CopyAwareEventStoreDecorator extends AbstractEventStoreDecorator
{
    public function load($id): DomainEventStream
    {
        return $this->loadCompleteStream(parent::load($id));
    }

    private function loadCompleteStream(DomainEventStream $eventStream): DomainEventStream
    {
        $events = iterator_to_array($eventStream);
        /** @var DomainMessage $oldestMessage */
        $oldestMessage = current($events);
        if ((int) ($oldestMessage->getPlayhead()) === 0) {
            return $eventStream;
        }

        $parentId = $this->identifyParent($oldestMessage);
        $parentEventStream = parent::load($parentId);

        $inheritedEvents = $this->limitEventStreamToPlayhead($parentEventStream, (int) $oldestMessage->getPlayhead());
        $combinedEvents = array_merge($inheritedEvents, $events);

        return $this->loadCompleteStream(new DomainEventStream($combinedEvents));
    }

    /**
     * @throws UnknownParentAggregateException
     */
    private function identifyParent(DomainMessage $message): string
    {
        /** @var AggregateCopiedEventInterface $domainEvent */
        $domainEvent = $message->getPayload();

        if (!$domainEvent instanceof AggregateCopiedEventInterface) {
            throw new UnknownParentAggregateException();
        }

        return $domainEvent->getParentAggregateId();
    }

    /**
     * @return DomainMessage[]
     */
    private function limitEventStreamToPlayhead(DomainEventStream $eventStream, int $playhead): array
    {
        return array_filter(
            iterator_to_array($eventStream),
            function (DomainMessage $message) use ($playhead) {
                return (int) ($message->getPlayhead()) < $playhead;
            }
        );
    }
}
