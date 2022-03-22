<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use Broadway\CommandHandling\CommandBus;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\Commands\OfferCommandFactoryInterface;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use CultuurNet\UDB3\StringLiteral;

final class EventEditingServiceTest extends TestCase
{
    private EventEditingService $eventEditingService;

    /**
     * @var UuidGeneratorInterface|MockObject
     */
    private $uuidGenerator;

    /**
     * @var DocumentRepository|MockObject
     */
    private $readRepository;

    protected function setUp(): void
    {
        $this->uuidGenerator = $this->createMock(UuidGeneratorInterface::class);
        $this->readRepository = $this->createMock(DocumentRepository::class);

        $this->eventEditingService = new EventEditingService(
            $this->createMock(CommandBus::class),
            $this->uuidGenerator,
            $this->readRepository,
            $this->createMock(OfferCommandFactoryInterface::class)
        );
    }

    /**
     * @test
     */
    public function it_refuses_to_update_title_of_unknown_event(): void
    {
        $id = 'some-unknown-id';

        $this->expectException(EntityNotFoundException::class);

        $this->setUpEventNotFound($id);

        $this->eventEditingService->updateTitle(
            $id,
            new Language('nl'),
            new StringLiteral('new title')
        );
    }

    /**
     * @test
     */
    public function it_refuses_to_update_the_description_of_unknown_event(): void
    {
        $id = 'some-unknown-id';

        $this->expectException(EntityNotFoundException::class);

        $this->setUpEventNotFound($id);

        $this->eventEditingService->updateDescription(
            $id,
            new Language('en'),
            new Description('new description')
        );
    }

    private function setUpEventNotFound($id): void
    {
        $this->readRepository->expects($this->once())
            ->method('fetch')
            ->with($id)
            ->willThrowException(DocumentDoesNotExist::withId($id));
    }
}
