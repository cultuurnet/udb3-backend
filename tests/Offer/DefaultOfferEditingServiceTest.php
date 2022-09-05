<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use Broadway\CommandHandling\CommandBus;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\Commands\OfferCommandFactoryInterface;
use CultuurNet\UDB3\Offer\Item\Commands\UpdateDescription;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DefaultOfferEditingServiceTest extends TestCase
{
    /**
     * @var CommandBus|MockObject
     */
    private $commandBus;

    /**
     * @var DocumentRepository|MockObject
     */
    private $offerRepository;

    /**
     * @var OfferCommandFactoryInterface|MockObject
     */
    private $commandFactory;

    private DefaultOfferEditingService $offerEditingService;

    private string $expectedCommandId;

    public function setUp()
    {
        $this->commandBus = $this->createMock(CommandBus::class);
        $uuidGenerator = $this->createMock(UuidGeneratorInterface::class);
        $this->offerRepository = $this->createMock(DocumentRepository::class);
        $this->commandFactory = $this->createMock(OfferCommandFactoryInterface::class);

        $this->offerEditingService = new DefaultOfferEditingService(
            $this->commandBus,
            $uuidGenerator,
            $this->offerRepository,
            $this->commandFactory
        );

        $this->expectedCommandId = '123456';
    }

    /**
     * @test
     */
    public function it_should_guard_that_a_document_exists_for_a_given_id(): void
    {
        $unknownId = '8FEFDA81-993D-4F33-851F-C19F8CB90712';

        $this->offerRepository->expects($this->once())
            ->method('fetch')
            ->with($unknownId)
            ->willThrowException(DocumentDoesNotExist::withId($unknownId));

        $this->expectException(EntityNotFoundException::class);

        $this->offerEditingService->guardId($unknownId);
    }
}
