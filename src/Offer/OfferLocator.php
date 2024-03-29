<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventSourcing\EventStreamDecorator;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;

class OfferLocator implements EventStreamDecorator
{
    private IriGeneratorInterface $iriGenerator;

    public function __construct(IriGeneratorInterface $iriGenerator)
    {
        $this->iriGenerator = $iriGenerator;
    }

    public function decorateForWrite(
        string $aggregateType,
        string $aggregateIdentifier,
        DomainEventStream $eventStream
    ): DomainEventStream {
        $offerLocation = $this->iriGenerator->iri($aggregateIdentifier);
        $messages = [];

        /** @var DomainMessage $message */
        foreach ($eventStream as $message) {
            $metadata = new Metadata(['id' => $offerLocation]);

            $messages[] = $message->andMetadata($metadata);
        }

        return new DomainEventStream($messages);
    }
}
