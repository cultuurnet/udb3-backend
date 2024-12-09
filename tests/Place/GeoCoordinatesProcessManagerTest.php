<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use Broadway\CommandHandling\CommandBus;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Address\Address as LegacyAddress;
use CultuurNet\UDB3\Address\CultureFeed\CultureFeedAddressFactory;
use CultuurNet\UDB3\Address\CultureFeed\CultureFeedAddressFactoryInterface;
use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PermanentCalendar;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Place\Commands\UpdateGeoCoordinatesFromAddress;
use CultuurNet\UDB3\Place\Events\AddressTranslated;
use CultuurNet\UDB3\Place\Events\AddressUpdated;
use CultuurNet\UDB3\Place\Events\MajorInfoUpdated;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Place\Events\PlaceImportedFromUDB2;
use CultuurNet\UDB3\Place\Events\PlaceUpdatedFromUDB2;
use CultuurNet\UDB3\Place\Events\TitleUpdated;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\SampleFiles;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class GeoCoordinatesProcessManagerTest extends TestCase
{
    /**
     * @var CommandBus&MockObject
     */
    private $commandBus;

    /**
     * @var LoggerInterface&MockObject
     */
    private $logger;

    private GeoCoordinatesProcessManager $processManager;

    /**
     * @var DocumentRepository&MockObject
     */
    private $documentRepository;

    public function setUp(): void
    {
        $this->commandBus = $this->createMock(CommandBus::class);
        $addressFactory = new CultureFeedAddressFactory();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->documentRepository = $this->createMock(DocumentRepository::class);

        $this->processManager = new GeoCoordinatesProcessManager(
            $this->commandBus,
            $addressFactory,
            $this->logger,
            $this->documentRepository
        );
    }

    /**
     * @test
     * @dataProvider addressEventDataProvider
     *
     */
    public function it_dispatches_a_geocoding_command_when_an_address_change_is_suspected(
        DomainMessage $event,
        UpdateGeoCoordinatesFromAddress $expectedCommand
    ): void {
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($expectedCommand);

        $this->processManager->handle($event);
    }

    /**
     * @test
     * @dataProvider missingAddressEventDataProvider
     *
     */
    public function it_does_not_dispatch_a_geocoding_command_when_a_cdbxml_import_or_update_is_missing_an_address(
        DomainMessage $event
    ): void {
        $this->commandBus->expects($this->never())
            ->method('dispatch');

        $this->processManager->handle($event);
    }

    /**
     * @test
     */
    public function it_should_not_dispatch_a_geocoding_command_when_an_address_is_translated(): void
    {
        $event = DomainMessage::recordNow(
            '4b735422-2bf3-4241-aabb-d70609d2d1d3',
            1,
            new Metadata([]),
            new AddressTranslated(
                '4b735422-2bf3-4241-aabb-d70609d2d1d3',
                LegacyAddress::fromUdb3ModelAddress(new Address(
                    new Street('Teststraat 1'),
                    new PostalCode('1000'),
                    new Locality('Bxl'),
                    new CountryCode('BE')
                )),
                new Language('fr')
            )
        );

        $this->commandBus->expects($this->never())
            ->method('dispatch');

        $this->processManager->handle($event);
    }

    /**
     * @test
     */
    public function it_logs_an_error_and_dispatches_no_command_when_a_cdbxml_import_or_update_has_an_invalid_address(): void
    {
        $domainMessage = DomainMessage::recordNow(
            '318F2ACB-F612-6F75-0037C9C29F44087A',
            0,
            new Metadata([]),
            new PlaceImportedFromUDB2(
                '318F2ACB-F612-6F75-0037C9C29F44087A',
                SampleFiles::read(__DIR__ . '/actor.xml'),
                'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
            )
        );

        /** @var CultureFeedAddressFactory&MockObject $addressFactory */
        $addressFactory = $this->createMock(CultureFeedAddressFactoryInterface::class);

        $documentRepository = $this->createMock(DocumentRepository::class);

        $processManager = new GeoCoordinatesProcessManager(
            $this->commandBus,
            $addressFactory,
            $this->logger,
            $documentRepository
        );

        $addressFactory->expects($this->once())
            ->method('fromCdbAddress')
            ->willThrowException(new \InvalidArgumentException('The given cdbxml address is missing a city'));

        $this->commandBus->expects($this->never())
            ->method('dispatch');

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Could not convert a cdbxml address to a udb3 address for geocoding.',
                [
                    'placeId' => '318F2ACB-F612-6F75-0037C9C29F44087A',
                    'error' => 'The given cdbxml address is missing a city',
                ]
            );

        $processManager->handle($domainMessage);
    }

    public function addressEventDataProvider(): array
    {
        return [
            'place_created' => [
                DomainMessage::recordNow(
                    '4b735422-2bf3-4241-aabb-d70609d2d1d3',
                    0,
                    new Metadata([]),
                    new PlaceCreated(
                        '4b735422-2bf3-4241-aabb-d70609d2d1d3',
                        new Language('es'),
                        'Het depot',
                        new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType()),
                        LegacyAddress::fromUdb3ModelAddress(new Address(
                            new Street('Teststraat 1'),
                            new PostalCode('1000'),
                            new Locality('Bxl'),
                            new CountryCode('BE')
                        )),
                        new PermanentCalendar(new OpeningHours())
                    )
                ),
                new UpdateGeoCoordinatesFromAddress(
                    '4b735422-2bf3-4241-aabb-d70609d2d1d3',
                    new Address(
                        new Street('Teststraat 1'),
                        new PostalCode('1000'),
                        new Locality('Bxl'),
                        new CountryCode('BE')
                    )
                ),
            ],
            'place_major_info_updated' => [
                DomainMessage::recordNow(
                    '4b735422-2bf3-4241-aabb-d70609d2d1d3',
                    1,
                    new Metadata([]),
                    new MajorInfoUpdated(
                        '4b735422-2bf3-4241-aabb-d70609d2d1d3',
                        'Het depot',
                        new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType()),
                        LegacyAddress::fromUdb3ModelAddress(new Address(
                            new Street('Teststraat 1'),
                            new PostalCode('1000'),
                            new Locality('Bxl'),
                            new CountryCode('BE')
                        )),
                        new Calendar(CalendarType::permanent())
                    )
                ),
                new UpdateGeoCoordinatesFromAddress(
                    '4b735422-2bf3-4241-aabb-d70609d2d1d3',
                    new Address(
                        new Street('Teststraat 1'),
                        new PostalCode('1000'),
                        new Locality('Bxl'),
                        new CountryCode('BE')
                    )
                ),
            ],
            'place_address_updated' => [
                DomainMessage::recordNow(
                    '4b735422-2bf3-4241-aabb-d70609d2d1d3',
                    1,
                    new Metadata([]),
                    new AddressUpdated(
                        '4b735422-2bf3-4241-aabb-d70609d2d1d3',
                        LegacyAddress::fromUdb3ModelAddress(new Address(
                            new Street('Teststraat 1'),
                            new PostalCode('1000'),
                            new Locality('Bxl'),
                            new CountryCode('BE')
                        ))
                    )
                ),
                new UpdateGeoCoordinatesFromAddress(
                    '4b735422-2bf3-4241-aabb-d70609d2d1d3',
                    new Address(
                        new Street('Teststraat 1'),
                        new PostalCode('1000'),
                        new Locality('Bxl'),
                        new CountryCode('BE')
                    )
                ),
            ],
            'place_imported_from_udb2_with_address' => [
                DomainMessage::recordNow(
                    '318F2ACB-F612-6F75-0037C9C29F44087A',
                    0,
                    new Metadata([]),
                    new PlaceImportedFromUDB2(
                        '318F2ACB-F612-6F75-0037C9C29F44087A',
                        SampleFiles::read(__DIR__ . '/actor.xml'),
                        'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
                    )
                ),
                new UpdateGeoCoordinatesFromAddress(
                    '318F2ACB-F612-6F75-0037C9C29F44087A',
                    new Address(
                        new Street('Jeugdlaan 2'),
                        new PostalCode('3900'),
                        new Locality('Overpelt'),
                        new CountryCode('BE')
                    )
                ),
            ],
            'place_updated_from_udb2_with_address' => [
                DomainMessage::recordNow(
                    '318F2ACB-F612-6F75-0037C9C29F44087A',
                    1,
                    new Metadata([]),
                    new PlaceUpdatedFromUDB2(
                        '318F2ACB-F612-6F75-0037C9C29F44087A',
                        SampleFiles::read(__DIR__ . '/actor.xml'),
                        'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
                    )
                ),
                new UpdateGeoCoordinatesFromAddress(
                    '318F2ACB-F612-6F75-0037C9C29F44087A',
                    new Address(
                        new Street('Jeugdlaan 2'),
                        new PostalCode('3900'),
                        new Locality('Overpelt'),
                        new CountryCode('BE')
                    )
                ),
            ],
        ];
    }

    public function missingAddressEventDataProvider(): array
    {
        return [
            'place_imported_from_udb2_without_address' => [
                DomainMessage::recordNow(
                    '318F2ACB-F612-6F75-0037C9C29F44087A',
                    0,
                    new Metadata([]),
                    new PlaceImportedFromUDB2(
                        '318F2ACB-F612-6F75-0037C9C29F44087A',
                        SampleFiles::read(__DIR__ . '/actor_without_contactinfo.xml'),
                        'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
                    )
                ),
                new UpdateGeoCoordinatesFromAddress(
                    '318F2ACB-F612-6F75-0037C9C29F44087A',
                    new Address(
                        new Street('Jeugdlaan 2'),
                        new PostalCode('3900'),
                        new Locality('Overpelt'),
                        new CountryCode('BE')
                    )
                ),
            ],
            'place_updated_from_udb2_without_address' => [
                DomainMessage::recordNow(
                    '318F2ACB-F612-6F75-0037C9C29F44087A',
                    1,
                    new Metadata([]),
                    new PlaceUpdatedFromUDB2(
                        '318F2ACB-F612-6F75-0037C9C29F44087A',
                        SampleFiles::read(__DIR__ . '/actor_without_contactinfo.xml'),
                        'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
                    )
                ),
                new UpdateGeoCoordinatesFromAddress(
                    '318F2ACB-F612-6F75-0037C9C29F44087A',
                    new Address(
                        new Street('Jeugdlaan 2'),
                        new PostalCode('3900'),
                        new Locality('Overpelt'),
                        new CountryCode('BE')
                    )
                ),
            ],
            'place_imported_from_udb2_with_virtual_address' => [
                DomainMessage::recordNow(
                    '318F2ACB-F612-6F75-0037C9C29F44087A',
                    0,
                    new Metadata([]),
                    new PlaceImportedFromUDB2(
                        '318F2ACB-F612-6F75-0037C9C29F44087A',
                        SampleFiles::read(__DIR__ . '/actor_without_physical_address.xml'),
                        'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
                    )
                ),
                new UpdateGeoCoordinatesFromAddress(
                    '318F2ACB-F612-6F75-0037C9C29F44087A',
                    new Address(
                        new Street('Jeugdlaan 2'),
                        new PostalCode('3900'),
                        new Locality('Overpelt'),
                        new CountryCode('BE')
                    )
                ),
            ],
            'place_updated_from_udb2_with_virtual_address' => [
                DomainMessage::recordNow(
                    '318F2ACB-F612-6F75-0037C9C29F44087A',
                    1,
                    new Metadata([]),
                    new PlaceUpdatedFromUDB2(
                        '318F2ACB-F612-6F75-0037C9C29F44087A',
                        SampleFiles::read(__DIR__ . '/actor_without_physical_address.xml'),
                        'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
                    )
                ),
                new UpdateGeoCoordinatesFromAddress(
                    '318F2ACB-F612-6F75-0037C9C29F44087A',
                    new Address(
                        new Street('Jeugdlaan 2'),
                        new PostalCode('3900'),
                        new Locality('Overpelt'),
                        new CountryCode('BE')
                    )
                ),
            ],
        ];
    }

    /**
     * @test
     */
    public function it_dispatches_a_geocoding_command_when_an_place_title_has_changed(): void
    {
        $this->documentRepository->expects($this->once())
            ->method('fetch')
            ->with('4b735422-2bf3-4241-aabb-d70609d2d1d3')
            ->willReturn(new JsonDocument('4b735422-2bf3-4241-aabb-d70609d2d1d3', Json::encode([
                'mainLanguage' => 'nl',
                'address' => [
                    'nl' => [
                        'addressCountry' => 'BE',
                        'addressLocality' => 'Leuven',
                        'postalCode' => '3000',
                        'streetAddress' => 'Bondgenotenlaan 1',
                    ],
                ],
            ])));

        $expectedCommand = new UpdateGeoCoordinatesFromAddress(
            '4b735422-2bf3-4241-aabb-d70609d2d1d3',
            new Address(
                new Street('Bondgenotenlaan 1'),
                new PostalCode('3000'),
                new Locality('Leuven'),
                new CountryCode('BE')
            )
        );

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($expectedCommand);

        $this->processManager->handle(
            DomainMessage::recordNow(
                '4b735422-2bf3-4241-aabb-d70609d2d1d3',
                1,
                new Metadata([]),
                new TitleUpdated(
                    '4b735422-2bf3-4241-aabb-d70609d2d1d3',
                    'Nieuwe plaatsnaam'
                ),
            ),
        );
    }
}
