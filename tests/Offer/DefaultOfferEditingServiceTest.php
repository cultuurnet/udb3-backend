<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use Broadway\CommandHandling\CommandBus;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\Commands\AbstractUpdatePriceInfo;
use CultuurNet\UDB3\Offer\Commands\AbstractUpdateTitle;
use CultuurNet\UDB3\Offer\Commands\OfferCommandFactoryInterface;
use CultuurNet\UDB3\Offer\Item\Commands\UpdateDescription;
use CultuurNet\UDB3\Offer\Item\Commands\UpdateTitle;
use CultuurNet\UDB3\PriceInfo\BasePrice;
use CultuurNet\UDB3\PriceInfo\PriceInfo;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Title;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DefaultOfferEditingServiceTest extends TestCase
{
    /**
     * @var CommandBus|MockObject
     */
    private $commandBus;

    /**
     * @var UuidGeneratorInterface|MockObject
     */
    private $uuidGenerator;

    /**
     * @var DocumentRepository|MockObject
     */
    private $offerRepository;

    /**
     * @var OfferCommandFactoryInterface|MockObject
     */
    private $commandFactory;

    /**
     * @var DefaultOfferEditingService
     */
    private $offerEditingService;

    /**
     * @var string
     */
    private $expectedCommandId;

    /**
     * @var AbstractUpdateTitle|MockObject
     */
    private $translateTitleCommand;

    public function setUp()
    {
        $this->commandBus = $this->createMock(CommandBus::class);
        $this->uuidGenerator = $this->createMock(UuidGeneratorInterface::class);
        $this->offerRepository = $this->createMock(DocumentRepository::class);
        $this->commandFactory = $this->createMock(OfferCommandFactoryInterface::class);

        $this->translateTitleCommand = $this->getMockForAbstractClass(
            AbstractUpdateTitle::class,
            ['foo', new Language('en'), new Title('English title')]
        );

        $this->offerEditingService = new DefaultOfferEditingService(
            $this->commandBus,
            $this->uuidGenerator,
            $this->offerRepository,
            $this->commandFactory
        );

        $this->expectedCommandId = '123456';
    }

    /**
     * @test
     */
    public function it_can_update_a_title_in_a_given_language()
    {
        $this->offerRepository->expects($this->once())
            ->method('fetch')
            ->with('foo')
            ->willReturn(new JsonDocument('foo'));

        $this->commandFactory->expects($this->once())
            ->method('createUpdateTitleCommand')
            ->with('foo', new Language('en'), new Title('English title'))
            ->willReturn(new UpdateTitle('foo', new Language('en'), new Title('English title')));

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->willReturn($this->expectedCommandId);

        $commandId = $this->offerEditingService->updateTitle(
            'foo',
            new Language('en'),
            new Title('English title')
        );

        $this->assertEquals($this->expectedCommandId, $commandId);
    }

    /**
     * @test
     */
    public function it_can_update_the_description_in_a_given_language()
    {
        $this->offerRepository->expects($this->once())
            ->method('fetch')
            ->with('foo')
            ->willReturn(new JsonDocument('foo'));

        $this->commandFactory->expects($this->once())
            ->method('createUpdateDescriptionCommand')
            ->with('foo', new Language('fr'), new Description('La description'))
            ->willReturn(new UpdateDescription('foo', new Language('fr'), new Description('La description')));

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->willReturn($this->expectedCommandId);

        $commandId = $this->offerEditingService->updateDescription(
            'foo',
            new Language('fr'),
            new Description('La description')
        );

        $this->assertEquals($this->expectedCommandId, $commandId);
    }

    /**
     * @test
     */
    public function it_should_guard_that_a_document_exists_for_a_given_id()
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
