<?php

namespace CultuurNet\UDB3\EventSourcing\DBAL;

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventSourcing\EventStreamDecorator;

class DummyEventStreamDecorator implements EventStreamDecorator
{
    public function decorateForWrite(
        $aggregateType,
        $aggregateIdentifier,
        DomainEventStream $eventStream
    ): DomainEventStream {
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
