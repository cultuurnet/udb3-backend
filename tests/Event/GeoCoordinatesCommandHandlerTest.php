<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Address\Formatter\FullAddressFormatter;
use CultuurNet\UDB3\Address\Formatter\LocalityAddressFormatter;
use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Event\Commands\UpdateGeoCoordinatesFromAddress;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\EventImportedFromUDB2;
use CultuurNet\UDB3\Event\Events\GeoCoordinatesUpdated;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Geocoding\Coordinate\Latitude;
use CultuurNet\UDB3\Geocoding\Coordinate\Longitude;
use CultuurNet\UDB3\Geocoding\GeocodingService;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarType;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\SampleFiles;
use CultuurNet\UDB3\Theme;
use PHPUnit\Framework\MockObject\MockObject;

class GeoCoordinatesCommandHandlerTest extends CommandHandlerScenarioTestCase
{
    private const EVENT_ID = '004aea08-e13d-48c9-b9eb-a18f20e6d44e';

    /**
     * @var GeocodingService&MockObject
     */
    private $geocodingService;

    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): GeoCoordinatesCommandHandler
    {
        $eventRepository = new EventRepository(
            $eventStore,
            $eventBus
        );

        $defaultAddressFormatter = new FullAddressFormatter();
        $localityAddressFormatter = new LocalityAddressFormatter();

        $this->geocodingService = $this->createMock(GeocodingService::class);

        $documentRepository = $this->createMock(DocumentRepository::class);
        $documentRepository->expects($this->once())
            ->method('fetch')
            ->with(self::EVENT_ID)
            ->willReturn(new JsonDocument(self::EVENT_ID, Json::encode([
                'name' => [
                    'nl' => 'Faith no More',
                    'fr' => 'Faith no More - a la francais',
                ],
            ])));

        return new GeoCoordinatesCommandHandler(
            $eventRepository,
            $defaultAddressFormatter,
            $localityAddressFormatter,
            $this->geocodingService,
            $documentRepository
        );
    }

    /**
     * @test
     */
    public function it_creates_coordinates_from_an_address_and_updates_them_on_the_given_event(): void
    {
        $eventId = self::EVENT_ID;

        $address = new Address(
            new Street('Wetstraat 1'),
            new PostalCode('1000'),
            new Locality('Bxl'),
            new CountryCode('BE')
        );

        $eventImported = new EventImportedFromUDB2(
            $eventId,
            SampleFiles::read(__DIR__ . '/samples/event_004aea08-e13d-48c9-b9eb-a18f20e6d44e.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL'
        );

        $command = new UpdateGeoCoordinatesFromAddress($eventId, $address);

        $coordinates = new Coordinates(
            new Latitude(-0.12),
            new Longitude(4.76)
        );

        $this->geocodingService->expects($this->once())
            ->method('getCoordinates')
            ->with('Wetstraat 1, 1000 Bxl, BE')
            ->willReturn($coordinates);

        $expectedEvent = new GeoCoordinatesUpdated($eventId, $coordinates);

        $this->scenario
            ->withAggregateId($eventId)
            ->given([$eventImported])
            ->when($command)
            ->then([$expectedEvent]);
    }

    /**
     * @test
     */
    public function it_has_a_fallback_to_locality_when_full_address_has_null_coordinates(): void
    {
        $eventId = self::EVENT_ID;

        $address = new Address(
            new Street('Wetstraat 1 (foutief)'),
            new PostalCode('1000'),
            new Locality('Bxl'),
            new CountryCode('BE')
        );

        $eventImported = new EventImportedFromUDB2(
            $eventId,
            SampleFiles::read(__DIR__ . '/samples/event_004aea08-e13d-48c9-b9eb-a18f20e6d44e.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL'
        );

        $command = new UpdateGeoCoordinatesFromAddress($eventId, $address);

        $coordinates = new Coordinates(
            new Latitude(-0.12),
            new Longitude(4.76)
        );

        $this->geocodingService->expects($this->exactly(2))
            ->method('getCoordinates')
            ->withConsecutive(
                [
                    'Wetstraat 1 (foutief), 1000 Bxl, BE',
                ],
                [
                    '1000 Bxl, BE',
                ]
            )
            ->willReturnOnConsecutiveCalls(null, $coordinates);

        $expectedEvent = new GeoCoordinatesUpdated($eventId, $coordinates);

        $this->scenario
            ->withAggregateId($eventId)
            ->given([$eventImported])
            ->when($command)
            ->then([$expectedEvent]);
    }


    /**
     * @test
     */
    public function it_skips_update_if_the_geo_coordinates_can_not_be_resolved(): void
    {
        $address = new Address(
            new Street('Wetstraat 1'),
            new PostalCode('1000'),
            new Locality('Bxl'),
            new CountryCode('BE')
        );

        $eventCreated = new EventCreated(
            self::EVENT_ID,
            new Language('en'),
            'Faith no More',
            new EventType('0.50.4.0.0', 'Concert'),
            new LocationId('7a59de16-6111-4658-aa6e-958ff855d14e'),
            new Calendar(CalendarType::permanent()),
            new Theme('1.8.1.0.0', 'Rock')
        );

        $command = new UpdateGeoCoordinatesFromAddress(self::EVENT_ID, $address);

        $this->geocodingService->expects($this->any())
            ->method('getCoordinates')
            ->willReturn(null);

        $this->scenario
            ->withAggregateId(self::EVENT_ID)
            ->given([$eventCreated])
            ->when($command)
            ->then([]);
    }
}
