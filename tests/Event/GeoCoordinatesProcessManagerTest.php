<?php

namespace CultuurNet\UDB3\Event;

use Broadway\CommandHandling\CommandBusInterface;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\CultureFeedAddressFactory;
use CultuurNet\UDB3\Address\CultureFeedAddressFactoryInterface;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Event\Commands\UpdateGeoCoordinatesFromAddress;
use CultuurNet\UDB3\Event\Events\EventImportedFromUDB2;
use CultuurNet\UDB3\Event\Events\EventUpdatedFromUDB2;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ValueObjects\Geography\Country;

class GeoCoordinatesProcessManagerTest extends TestCase
{
    /**
     * @var CommandBusInterface|MockObject
     */
    private $commandBus;

    /**
     * @var CultureFeedAddressFactoryInterface
     */
    private $addressFactory;

    /**
     * @var GeoCoordinatesProcessManager
     */
    private $processManager;

    public function setUp()
    {
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->addressFactory = new CultureFeedAddressFactory();

        $this->processManager = new GeoCoordinatesProcessManager(
            $this->commandBus,
            $this->addressFactory,
            new NullLogger()
        );
    }

    /**
     * @test
     */
    public function it_handles_event_imported_from_udb2_with_dummy_location()
    {
        $domainMessage = DomainMessage::recordNow(
            'e3604613-af01-4d2b-8cee-13ab61b89651',
            0,
            new Metadata(),
            new EventImportedFromUDB2(
                'e3604613-af01-4d2b-8cee-13ab61b89651',
                file_get_contents(__DIR__ . '/samples/geocoding/event_with_dummy_location.cdbxml.xml'),
                'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
            )
        );

        $expectedCommand = new UpdateGeoCoordinatesFromAddress(
            'e3604613-af01-4d2b-8cee-13ab61b89651',
            new Address(
                new Street('Martelarenplein 1'),
                new PostalCode('3000'),
                new Locality('Leuven'),
                Country::fromNative('BE')
            )
        );

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($expectedCommand);

        $this->processManager->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_handles_event_updated_from_udb2_with_dummy_location()
    {
        $domainMessage = DomainMessage::recordNow(
            'e3604613-af01-4d2b-8cee-13ab61b89651',
            0,
            new Metadata(),
            new EventUpdatedFromUDB2(
                'e3604613-af01-4d2b-8cee-13ab61b89651',
                file_get_contents(__DIR__ . '/samples/geocoding/event_with_dummy_location.cdbxml.xml'),
                'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
            )
        );

        $expectedCommand = new UpdateGeoCoordinatesFromAddress(
            'e3604613-af01-4d2b-8cee-13ab61b89651',
            new Address(
                new Street('Martelarenplein 1'),
                new PostalCode('3000'),
                new Locality('Leuven'),
                Country::fromNative('BE')
            )
        );

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($expectedCommand);

        $this->processManager->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_does_not_handle_event_without_physical_address()
    {
        $domainMessage = DomainMessage::recordNow(
            'e3604613-af01-4d2b-8cee-13ab61b89651',
            0,
            new Metadata(),
            new EventImportedFromUDB2(
                'e3604613-af01-4d2b-8cee-13ab61b89651',
                file_get_contents(__DIR__ . '/samples/geocoding/event_with_dummy_location_without_physical_address.cdbxml.xml'),
                'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
            )
        );

        $this->commandBus->expects($this->never())
            ->method('dispatch');

        $this->processManager->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_does_not_handle_event_with_location()
    {
        $domainMessage = DomainMessage::recordNow(
            'e3604613-af01-4d2b-8cee-13ab61b89651',
            0,
            new Metadata(),
            new EventImportedFromUDB2(
                'e3604613-af01-4d2b-8cee-13ab61b89651',
                file_get_contents(__DIR__ . '/samples/geocoding/event_with_location.cdbxml.xml'),
                'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
            )
        );

        $this->commandBus->expects($this->never())
            ->method('dispatch');

        $this->processManager->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_does_not_handle_event_with_external_location()
    {
        $domainMessage = DomainMessage::recordNow(
            'e3604613-af01-4d2b-8cee-13ab61b89651',
            0,
            new Metadata(),
            new EventImportedFromUDB2(
                'e3604613-af01-4d2b-8cee-13ab61b89651',
                file_get_contents(__DIR__ . '/samples/geocoding/event_with_external_location.cdbxml.xml'),
                'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
            )
        );

        $this->commandBus->expects($this->never())
            ->method('dispatch');

        $this->processManager->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_throws_parse_exception_for_event_without_location()
    {
        $domainMessage = DomainMessage::recordNow(
            'e3604613-af01-4d2b-8cee-13ab61b89651',
            0,
            new Metadata(),
            new EventImportedFromUDB2(
                'e3604613-af01-4d2b-8cee-13ab61b89651',
                file_get_contents(__DIR__ . '/samples/geocoding/event_without_location.cdbxml.xml'),
                'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
            )
        );

        $this->expectException(\CultureFeed_Cdb_ParseException::class);

        $this->processManager->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_throws_parse_exception_for_event_without_address()
    {
        $domainMessage = DomainMessage::recordNow(
            'e3604613-af01-4d2b-8cee-13ab61b89651',
            0,
            new Metadata(),
            new EventImportedFromUDB2(
                'e3604613-af01-4d2b-8cee-13ab61b89651',
                file_get_contents(__DIR__ . '/samples/geocoding/event_with_dummy_location_without_address.cdbxml.xml'),
                'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
            )
        );

        $this->expectException(\CultureFeed_Cdb_ParseException::class);

        $this->processManager->handle($domainMessage);
    }
}
