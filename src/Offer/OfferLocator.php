<?php

namespace CultuurNet\UDB3\Offer;

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainEventStreamInterface;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventSourcing\EventStreamDecoratorInterface;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;

class OfferLocator implements EventStreamDecoratorInterface
{
    /**
     * @var IriGeneratorInterface
     */
    private $iriGenerator;

    /**
     * OfferLocator constructor.
     * @param IriGeneratorInterface $iriGenerator
     */
    public function __construct(IriGeneratorInterface $iriGenerator)
    {
        $this->iriGenerator = $iriGenerator;
    }

    public function decorateForWrite($aggregateType, $aggregateIdentifier, DomainEventStreamInterface $eventStream)
    {
        $offerLocation = $this->iriGenerator->iri($aggregateIdentifier);
        $messages = array();

        /** @var DomainMessage $message */
        foreach ($eventStream as $message) {
            $metadata = new Metadata(['id' => $offerLocation]);

            $messages[] = $message->andMetadata($metadata);
        }

        return new DomainEventStream($messages);
    }
}
