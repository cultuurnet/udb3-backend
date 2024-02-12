<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Formatter\AddressFormatter;
use CultuurNet\UDB3\Address\Formatter\FullAddressFormatter;
use CultuurNet\UDB3\Address\Formatter\LocalityAddressFormatter;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Calendar\CalendarType;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Geocoding\Coordinate\Latitude;
use CultuurNet\UDB3\Geocoding\Coordinate\Longitude;
use CultuurNet\UDB3\Geocoding\GeocodingService;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Place\Commands\UpdateGeoCoordinatesFromAddress;
use CultuurNet\UDB3\Place\Events\GeoCoordinatesUpdated;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\MockObject\MockObject;

class GeoCoordinatesCommandHandlerTest extends CommandHandlerScenarioTestCase
{
    private const PLACE_ID = 'b9ec8a0a-ec9d-4dd3-9aaa-6d5b41b69d7c';
    private AddressFormatter $defaultAddressFormatter;

    private AddressFormatter $localityAddressFormatter;

    /**
     * @var GeocodingService|MockObject
     */
    private $geocodingService;

    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): GeoCoordinatesCommandHandler
    {
        $repository = new PlaceRepository(
            $eventStore,
            $eventBus
        );

        $this->defaultAddressFormatter = new FullAddressFormatter();
        $this->localityAddressFormatter = new LocalityAddressFormatter();

        $this->geocodingService = $this->createMock(GeocodingService::class);

        $documentRepository = $this->createMock(DocumentRepository::class);
        $documentRepository->expects($this->once())
            ->method('fetch')
            ->with(self::PLACE_ID)
            ->willReturn(new JsonDocument(self::PLACE_ID, json_encode([
                'name' => [
                    'nl' => 'Faith no More',
                    'fr' => 'Faith no More - a la francais',

                ],
            ], JSON_THROW_ON_ERROR)));

        return new GeoCoordinatesCommandHandler(
            $repository,
            $this->defaultAddressFormatter,
            $this->localityAddressFormatter,
            $this->geocodingService,
            $documentRepository,
            true,
        );
    }

    /**
     * @test
     */
    public function it_creates_coordinates_from_an_address_and_updates_them_on_the_given_place(): void
    {
        $address = new Address(
            new Street('Wetstraat 1'),
            new PostalCode('1000'),
            new Locality('Bxl'),
            new CountryCode('BE')
        );

        $placeCreated = new PlaceCreated(
            self::PLACE_ID,
            new Language('en'),
            'Some place',
            new EventType('01.01', 'Some category'),
            $address,
            new Calendar(CalendarType::PERMANENT())
        );

        $command = new UpdateGeoCoordinatesFromAddress(self::PLACE_ID, $address);

        $coordinates = new Coordinates(
            new Latitude(-0.12),
            new Longitude(4.76)
        );

        $this->geocodingService->expects($this->once())
            ->method('getCoordinates')
            ->with('Wetstraat 1, 1000 Bxl, BE')
            ->willReturn($coordinates);

        $expectedEvent = new GeoCoordinatesUpdated(self::PLACE_ID, $coordinates);

        $this->scenario
            ->withAggregateId(self::PLACE_ID)
            ->given([$placeCreated])
            ->when($command)
            ->then([$expectedEvent]);
    }

    /**
     * @test
     */
    public function it_has_a_fallback_to_locality_when_full_address_has_null_coordinates(): void
    {
        $address = new Address(
            new Street('Wetstraat 1 (foutief)'),
            new PostalCode('1000'),
            new Locality('Bxl'),
            new CountryCode('BE')
        );

        $placeCreated = new PlaceCreated(
            self::PLACE_ID,
            new Language('en'),
            'Some place',
            new EventType('01.01', 'Some category'),
            $address,
            new Calendar(CalendarType::PERMANENT())
        );

        $command = new UpdateGeoCoordinatesFromAddress(self::PLACE_ID, $address);

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

        $expectedEvent = new GeoCoordinatesUpdated(self::PLACE_ID, $coordinates);

        $this->scenario
            ->withAggregateId(self::PLACE_ID)
            ->given([$placeCreated])
            ->when($command)
            ->then([$expectedEvent]);
    }
}
