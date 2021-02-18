<?php

namespace CultuurNet\UDB3\Event;

use Broadway\Repository\AggregateNotFoundException;
use Broadway\Repository\Repository;
use CultuurNet\UDB3\Event\ReadModel\Relations\RepositoryInterface as RelationsRepositoryInterface;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LocalEventServiceTest extends TestCase
{
    /**
     * @var LocalEventService
     */
    protected $eventService;

    /**
     * @var DocumentRepository|MockObject
     */
    protected $documentRepository;

    /**
     * @var Repository|MockObject
     */
    protected $eventRepository;

    /**
     * @var IriGeneratorInterface|MockObject
     */
    protected $iriGenerator;

    /**
     * @var RelationsRepositoryInterface|MockObject
     */
    protected $eventRelationsRepository;

    public function setUp()
    {
        $this->documentRepository = $this->createMock(DocumentRepository::class);
        $this->eventRepository = $this->createMock(Repository::class);
        $this->eventRelationsRepository = $this->createMock(RelationsRepositoryInterface::class);

        $this->iriGenerator = $this->createMock(IriGeneratorInterface::class);
        $this->iriGenerator->expects($this->any())
            ->method('iri')
            ->willReturnCallback(
                function ($id) {
                    return "event/{$id}";
                }
            );

        $this->eventService = new LocalEventService(
            $this->documentRepository,
            $this->eventRepository,
            $this->eventRelationsRepository,
            $this->iriGenerator
        );
    }

    /**
     * @test
     */
    public function it_throws_an_EventNotFoundException_when_aggregate_is_not_found_in_event_repository()
    {
        $id = 'some-unknown-id';

        $this->eventRepository->expects($this->once())
            ->method('load')
            ->with($id)
            ->willThrowException(new AggregateNotFoundException());

        $this->expectException(EventNotFoundException::class);

        $this->eventService->getEvent($id);
    }
}
