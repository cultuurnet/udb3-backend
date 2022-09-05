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
     * @var DocumentRepository|MockObject
     */
    private $readRepository;

    protected function setUp(): void
    {
        $uuidGenerator = $this->createMock(UuidGeneratorInterface::class);
        $this->readRepository = $this->createMock(DocumentRepository::class);

        $this->eventEditingService = new EventEditingService(
            $this->createMock(CommandBus::class),
            $uuidGenerator,
            $this->readRepository,
            $this->createMock(OfferCommandFactoryInterface::class)
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
