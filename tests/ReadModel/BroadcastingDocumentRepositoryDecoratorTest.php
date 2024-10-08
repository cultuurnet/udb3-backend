<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\ReadModel;

use Broadway\EventHandling\EventBus;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BroadcastingDocumentRepositoryDecoratorTest extends TestCase
{
    /**
     * @var EventBus&MockObject
     */
    protected $eventBus;

    /**
     * @var DocumentRepository&MockObject
     */
    protected $decoratedRepository;

    protected BroadcastingDocumentRepositoryDecorator $repository;

    /**
     * @var DocumentEventFactory&MockObject
     */
    protected $eventFactory;

    public function setUp(): void
    {
        $this->decoratedRepository = $this->createMock(DocumentRepository::class);
        $this->eventBus = $this->createMock(EventBus::class);
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
    public function it_broadcasts_when_a_document_is_saved(): void
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
