<?php

namespace CultuurNet\UDB3\ReadModel;

use Broadway\EventHandling\EventBusInterface;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BroadcastingDocumentRepositoryDecoratorTest extends TestCase
{
    /**
     * @var EventBusInterface|MockObject
     */
    protected $eventBus;

    /**
     * @var DocumentRepositoryInterface|MockObject
     */
    protected $decoratedRepository;

    /**
     * @var BroadcastingDocumentRepositoryDecorator
     */
    protected $repository;

    /**
     * @var DocumentEventFactory|MockObject
     */
    protected $eventFactory;

    public function setUp()
    {
        $this->decoratedRepository = $this->createMock(DocumentRepositoryInterface::class);
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->eventFactory = $this->createMock(DocumentEventFactory::class);

        $this->repository = new BroadcastingDocumentRepositoryDecorator(
            $this->decoratedRepository,
            $this->eventBus,
            $this->eventFactory
        );
    }

    /**
     * @test
     */
    public function it_broadcasts_when_a_document_is_saved()
    {
        $document = new JsonDocument('some-document-id', '{"nice":"body"}');

        // the provided factory should be used to create a new event
        $this->eventFactory->expects($this->once())
            ->method('createEvent')
            ->with('some-document-id');

        // when saving the event it should also save the document in the decorated repository
        $this->decoratedRepository->expects($this->once())
            ->method('save')
            ->with($document);

        $this->eventBus->expects($this->once())
            ->method('publish');

        $this->repository->save($document);
    }
}
