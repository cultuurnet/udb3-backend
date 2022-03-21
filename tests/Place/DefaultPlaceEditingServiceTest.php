<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use Broadway\CommandHandling\CommandBus;
use Broadway\EventStore\InMemoryEventStore;
use Broadway\EventStore\TraceableEventStore;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Offer\Commands\OfferCommandFactoryInterface;
use CultuurNet\UDB3\Place\Commands\UpdateAddress;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DefaultPlaceEditingServiceTest extends TestCase
{
    /**
     * @var DefaultPlaceEditingService
     */
    protected $placeEditingService;

    /**
     * @var CommandBus|MockObject
     */
    protected $commandBus;

    /**
     * @var UuidGeneratorInterface|MockObject
     */
    protected $uuidGenerator;

    /**
     * @var OfferCommandFactoryInterface|MockObject
     */
    protected $commandFactory;

    /**
     * @var DocumentRepository|MockObject
     */
    protected $readRepository;

    /**
     * @var TraceableEventStore
     */
    protected $eventStore;

    public function setUp()
    {
        $this->commandBus = $this->createMock(CommandBus::class);

        $this->uuidGenerator = $this->createMock(
            UuidGeneratorInterface::class
        );

        $this->commandFactory = $this->createMock(OfferCommandFactoryInterface::class);

        $this->readRepository = $this->createMock(DocumentRepository::class);

        $this->eventStore = new TraceableEventStore(
            new InMemoryEventStore()
        );

        $this->readRepository->expects($this->any())
            ->method('fetch')
            ->with('ad93103d-1395-4af7-a52a-2829d466c232')
            ->willReturn(new JsonDocument('ad93103d-1395-4af7-a52a-2829d466c232'));

        $this->placeEditingService = new DefaultPlaceEditingService(
            $this->commandBus,
            $this->uuidGenerator,
            $this->readRepository,
            $this->commandFactory
        );
    }

    /**
     * @test
     */
    public function it_should_update_the_address_of_a_place_by_dispatching_a_relevant_command()
    {
        $id = 'ad93103d-1395-4af7-a52a-2829d466c232';
        $address = new Address(
            new Street('Eenmeilaan 35'),
            new PostalCode('3010'),
            new Locality('Kessel-Lo'),
            new CountryCode('BE')
        );
        $language = new Language('nl');

        $expectedCommandId = '98994a85-f0d9-4862-a91e-02f116bd609b';

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with(new UpdateAddress($id, $address, $language))
            ->willReturn($expectedCommandId);

        $actualCommandId = $this->placeEditingService->updateAddress($id, $address, $language);

        $this->assertEquals($expectedCommandId, $actualCommandId);
    }
}
