<?php

namespace CultuurNet\UDB3\EventSourcing\DBAL;

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainEventStreamInterface;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventSourcing\EventStreamDecoratorInterface;

class DummyEventStreamDecorator implements EventStreamDecoratorInterface
{
    public function decorateForWrite($aggregateType, $aggregateIdentifier, DomainEventStreamInterface $eventStream)
    {
        $messages = [];

        /** @var DomainMessage $message */
        foreach ($eventStream as $message) {
            $metadata = new Metadata(
                [
                    'mock' => $aggregateType . '::' . $aggregateIdentifier,
                ]
            );

            $messages[] = $message->andMetadata($metadata);
        }

        return new DomainEventStream($messages);
    }
}
