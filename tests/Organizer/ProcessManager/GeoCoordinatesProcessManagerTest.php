<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\ProcessManager;

use Broadway\CommandHandling\CommandBus;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\CultureFeedAddressFactory;
use CultuurNet\UDB3\Address\CultureFeedAddressFactoryInterface;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Organizer\Commands\UpdateGeoCoordinatesFromAddress;
use CultuurNet\UDB3\Organizer\Events\AddressTranslated;
use CultuurNet\UDB3\Organizer\Events\AddressUpdated;
use CultuurNet\UDB3\Organizer\Events\OrganizerImportedFromUDB2;
use CultuurNet\UDB3\Organizer\Events\OrganizerUpdatedFromUDB2;
use CultuurNet\UDB3\SampleFiles;
use InvalidArgumentException;
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

    public function setUp(): void
    {
        $this->commandBus = $this->createMock(CommandBus::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->processManager = new GeoCoordinatesProcessManager(
            $this->commandBus,
            new CultureFeedAddressFactory(),
            $this->logger
        );
    }

    /**
     * @test
     * @dataProvider addressEventDataProvider
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
                'Teststraat 1',
                '1000',
                'Bxl',
                'BE',
                'fr'
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
            new OrganizerImportedFromUDB2(
                '318F2ACB-F612-6F75-0037C9C29F44087A',
                SampleFiles::read(__DIR__ . '/../Samples/actor.xml'),
                'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
            )
        );

        /** @var CultureFeedAddressFactory&MockObject $addressFactory */
        $addressFactory = $this->createMock(CultureFeedAddressFactoryInterface::class);

        $processManager = new GeoCoordinatesProcessManager(
            $this->commandBus,
            $addressFactory,
            $this->logger
        );

        $addressFactory->expects($this->once())
            ->method('fromCdbAddress')
            ->willThrowException(new InvalidArgumentException('The given cdbxml address is missing a city'));

        $this->commandBus->expects($this->never())
            ->method('dispatch');

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Could not convert a cdbxml address to a udb3 address for geocoding.',
                [
                    'organizerId' => '318F2ACB-F612-6F75-0037C9C29F44087A',
                    'error' => 'The given cdbxml address is missing a city',
                ]
            );

        $processManager->handle($domainMessage);
    }

    public function addressEventDataProvider(): array
    {
        return [
            'organizer_address_updated' => [
                DomainMessage::recordNow(
                    '4b735422-2bf3-4241-aabb-d70609d2d1d3',
                    1,
                    new Metadata([]),
                    new AddressUpdated(
                        '4b735422-2bf3-4241-aabb-d70609d2d1d3',
                        'Teststraat 1',
                        '1000',
                        'Bxl',
                        'BE'
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
            'organizer_imported_from_udb2_with_address' => [
                DomainMessage::recordNow(
                    '318F2ACB-F612-6F75-0037C9C29F44087A',
                    0,
                    new Metadata([]),
                    new OrganizerImportedFromUDB2(
                        '318F2ACB-F612-6F75-0037C9C29F44087A',
                        SampleFiles::read(__DIR__ . '/../Samples/actor.xml'),
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
            'organizer_updated_from_udb2_with_address' => [
                DomainMessage::recordNow(
                    '318F2ACB-F612-6F75-0037C9C29F44087A',
                    1,
                    new Metadata([]),
                    new OrganizerUpdatedFromUDB2(
                        '318F2ACB-F612-6F75-0037C9C29F44087A',
                        SampleFiles::read(__DIR__ . '/../Samples/actor.xml'),
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
            'organizer_imported_from_udb2_without_address' => [
                DomainMessage::recordNow(
                    '318F2ACB-F612-6F75-0037C9C29F44087A',
                    0,
                    new Metadata([]),
                    new OrganizerImportedFromUDB2(
                        '318F2ACB-F612-6F75-0037C9C29F44087A',
                        SampleFiles::read(__DIR__ . '/../Samples/actor_without_contactinfo.xml'),
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
            'organizer_updated_from_udb2_without_address' => [
                DomainMessage::recordNow(
                    '318F2ACB-F612-6F75-0037C9C29F44087A',
                    1,
                    new Metadata([]),
                    new OrganizerUpdatedFromUDB2(
                        '318F2ACB-F612-6F75-0037C9C29F44087A',
                        SampleFiles::read(__DIR__ . '/../Samples/actor_without_contactinfo.xml'),
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
            'organizer_imported_from_udb2_with_virtual_address' => [
                DomainMessage::recordNow(
                    '318F2ACB-F612-6F75-0037C9C29F44087A',
                    0,
                    new Metadata([]),
                    new OrganizerImportedFromUDB2(
                        '318F2ACB-F612-6F75-0037C9C29F44087A',
                        SampleFiles::read(__DIR__ . '/../Samples/actor_without_physical_address.xml'),
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
            'organizer_updated_from_udb2_with_virtual_address' => [
                DomainMessage::recordNow(
                    '318F2ACB-F612-6F75-0037C9C29F44087A',
                    1,
                    new Metadata([]),
                    new OrganizerUpdatedFromUDB2(
                        '318F2ACB-F612-6F75-0037C9C29F44087A',
                        SampleFiles::read(__DIR__ . '/../Samples/actor_without_physical_address.xml'),
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
}
