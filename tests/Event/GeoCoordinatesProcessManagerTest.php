<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use Broadway\CommandHandling\CommandBus;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Address\CultureFeedAddressFactory;
use CultuurNet\UDB3\Event\Commands\UpdateGeoCoordinatesFromAddress;
use CultuurNet\UDB3\Event\Events\EventImportedFromUDB2;
use CultuurNet\UDB3\Event\Events\EventUpdatedFromUDB2;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
use CultuurNet\UDB3\SampleFiles;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class GeoCoordinatesProcessManagerTest extends TestCase
{
    /**
     * @var CommandBus&MockObject
     */
    private $commandBus;

    private GeoCoordinatesProcessManager $processManager;

    public function setUp(): void
    {
        $this->commandBus = $this->createMock(CommandBus::class);
        $addressFactory = new CultureFeedAddressFactory();

        $this->processManager = new GeoCoordinatesProcessManager(
            $this->commandBus,
            $addressFactory,
            new NullLogger()
        );
    }

    /**
     * @test
     */
    public function it_handles_event_imported_from_udb2_with_dummy_location(): void
    {
        $domainMessage = DomainMessage::recordNow(
            'e3604613-af01-4d2b-8cee-13ab61b89651',
            0,
            new Metadata(),
            new EventImportedFromUDB2(
                'e3604613-af01-4d2b-8cee-13ab61b89651',
                SampleFiles::read(__DIR__ . '/samples/geocoding/event_with_dummy_location.cdbxml.xml'),
                'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
            )
        );

        $expectedCommand = new UpdateGeoCoordinatesFromAddress(
            'e3604613-af01-4d2b-8cee-13ab61b89651',
            new Address(
                new Street('Martelarenplein 1'),
                new PostalCode('3000'),
                new Locality('Leuven'),
                new CountryCode('BE')
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
    public function it_handles_event_updated_from_udb2_with_dummy_location(): void
    {
        $domainMessage = DomainMessage::recordNow(
            'e3604613-af01-4d2b-8cee-13ab61b89651',
            0,
            new Metadata(),
            new EventUpdatedFromUDB2(
                'e3604613-af01-4d2b-8cee-13ab61b89651',
                SampleFiles::read(__DIR__ . '/samples/geocoding/event_with_dummy_location.cdbxml.xml'),
                'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
            )
        );

        $expectedCommand = new UpdateGeoCoordinatesFromAddress(
            'e3604613-af01-4d2b-8cee-13ab61b89651',
            new Address(
                new Street('Martelarenplein 1'),
                new PostalCode('3000'),
                new Locality('Leuven'),
                new CountryCode('BE')
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
    public function it_does_not_handle_event_without_physical_address(): void
    {
        $domainMessage = DomainMessage::recordNow(
            'e3604613-af01-4d2b-8cee-13ab61b89651',
            0,
            new Metadata(),
            new EventImportedFromUDB2(
                'e3604613-af01-4d2b-8cee-13ab61b89651',
                SampleFiles::read(__DIR__ . '/samples/geocoding/event_with_dummy_location_without_physical_address.cdbxml.xml'),
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
    public function it_does_not_handle_event_with_location(): void
    {
        $domainMessage = DomainMessage::recordNow(
            'e3604613-af01-4d2b-8cee-13ab61b89651',
            0,
            new Metadata(),
            new EventImportedFromUDB2(
                'e3604613-af01-4d2b-8cee-13ab61b89651',
                SampleFiles::read(__DIR__ . '/samples/geocoding/event_with_location.cdbxml.xml'),
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
    public function it_does_not_handle_event_with_external_location(): void
    {
        $domainMessage = DomainMessage::recordNow(
            'e3604613-af01-4d2b-8cee-13ab61b89651',
            0,
            new Metadata(),
            new EventImportedFromUDB2(
                'e3604613-af01-4d2b-8cee-13ab61b89651',
                SampleFiles::read(__DIR__ . '/samples/geocoding/event_with_external_location.cdbxml.xml'),
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
    public function it_throws_parse_exception_for_event_without_location(): void
    {
        $domainMessage = DomainMessage::recordNow(
            'e3604613-af01-4d2b-8cee-13ab61b89651',
            0,
            new Metadata(),
            new EventImportedFromUDB2(
                'e3604613-af01-4d2b-8cee-13ab61b89651',
                SampleFiles::read(__DIR__ . '/samples/geocoding/event_without_location.cdbxml.xml'),
                'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
            )
        );

        $this->expectException(\CultureFeed_Cdb_ParseException::class);

        $this->processManager->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_throws_parse_exception_for_event_without_address(): void
    {
        $domainMessage = DomainMessage::recordNow(
            'e3604613-af01-4d2b-8cee-13ab61b89651',
            0,
            new Metadata(),
            new EventImportedFromUDB2(
                'e3604613-af01-4d2b-8cee-13ab61b89651',
                SampleFiles::read(__DIR__ . '/samples/geocoding/event_with_dummy_location_without_address.cdbxml.xml'),
                'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
            )
        );

        $this->expectException(\CultureFeed_Cdb_ParseException::class);

        $this->processManager->handle($domainMessage);
    }
}
