<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\ReadModel;

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventBus;
use Broadway\UuidGenerator\Rfc4122\Version4Generator;

/**
 * Class BroadcastingDocumentRepositoryDecorator
 *  This decorator will broadcast an event every time a document is saved
 * @package CultuurNet\UDB3\Event\ReadModel
 */
class BroadcastingDocumentRepositoryDecorator extends DocumentRepositoryDecorator
{
    protected DocumentEventFactory $eventFactory;

    protected EventBus $eventBus;

    public function __construct(
        DocumentRepository $repository,
        EventBus $eventBus,
        DocumentEventFactory $eventFactory
    ) {
        parent::__construct($repository);
        $this->eventFactory = $eventFactory;
        $this->eventBus = $eventBus;
    }

    public function save(JsonDocument $document): void
    {
        parent::save($document);

        $this->broadcastDocumentUpdated($document->getId());
    }

    protected function broadcastDocumentUpdated(string $id): void
    {
        $event = $this->eventFactory->createEvent($id);

        $generator = new Version4Generator();
        $events = [
            DomainMessage::recordNow(
                $generator->generate(),
                1,
                new Metadata(),
                $event
            ),
        ];

        $this->eventBus->publish(
            new DomainEventStream($events)
        );
    }
}
