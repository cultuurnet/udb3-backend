<?php

namespace CultuurNet\UDB3\ReadModel;

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventBusInterface;
use Broadway\UuidGenerator\Rfc4122\Version4Generator;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryDecorator;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;

/**
 * Class BroadcastingDocumentRepositoryDecorator
 *  This decorator will broadcast an event every time a document is saved
 * @package CultuurNet\UDB3\Event\ReadModel
 */
class BroadcastingDocumentRepositoryDecorator extends DocumentRepositoryDecorator
{
    /**
     * @var DocumentEventFactory
     */
    protected $eventFactory;

    /**
     * @var EventBusInterface
     */
    protected $eventBus;

    public function __construct(
        DocumentRepositoryInterface $repository,
        EventBusInterface $eventBus,
        DocumentEventFactory $eventFactory
    ) {
        parent::__construct($repository);
        $this->eventFactory = $eventFactory;
        $this->eventBus = $eventBus;
    }

    public function save(JsonDocument $readModel)
    {
        parent::save($readModel);

        $this->broadcastDocumentUpdated($readModel->getId());
    }

    protected function broadcastDocumentUpdated($id)
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
