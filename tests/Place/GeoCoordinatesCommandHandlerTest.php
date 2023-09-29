<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Geocoding\Coordinate\Latitude;
use CultuurNet\UDB3\Geocoding\Coordinate\Longitude;
use CultuurNet\UDB3\Geocoding\GeocodingService;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\AddressFormatter;
use CultuurNet\UDB3\Address\FullAddressFormatter;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\LocalityAddressFormatter;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Calendar\CalendarType;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Place\Commands\UpdateGeoCoordinatesFromAddress;
use CultuurNet\UDB3\Place\Events\GeoCoordinatesUpdated;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\MockObject\MockObject;

class GeoCoordinatesCommandHandlerTest extends CommandHandlerScenarioTestCase
{
    /**
     * @var AddressFormatter
     */
    private $defaultAddressFormatter;

    /**
     * @var AddressFormatter
     */
    private $localityAddressFormatter;

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

        return new GeoCoordinatesCommandHandler(
            $repository,
            $this->defaultAddressFormatter,
            $this->localityAddressFormatter,
            $this->geocodingService
        );
    }

    /**
     * @test
     */
    public function it_creates_coordinates_from_an_address_and_updates_them_on_the_given_place(): void
    {
        $id = 'b9ec8a0a-ec9d-4dd3-9aaa-6d5b41b69d7c';

        $address = new Address(
            new Street('Wetstraat 1'),
            new PostalCode('1000'),
            new Locality('Bxl'),
            new CountryCode('BE')
        );

        $placeCreated = new PlaceCreated(
            $id,
            new Language('en'),
            new Title('Some place'),
            new EventType('01.01', 'Some category'),
            $address,
            new Calendar(CalendarType::PERMANENT())
        );

        $command = new UpdateGeoCoordinatesFromAddress($id, $address);

        $coordinates = new Coordinates(
            new Latitude(-0.12),
            new Longitude(4.76)
        );

        $this->geocodingService->expects($this->once())
            ->method('getCoordinates')
            ->with('Wetstraat 1, 1000 Bxl, BE')
            ->willReturn($coordinates);

        $expectedEvent = new GeoCoordinatesUpdated($id, $coordinates);

        $this->scenario
            ->withAggregateId($id)
            ->given([$placeCreated])
            ->when($command)
            ->then([$expectedEvent]);
    }

    /**
     * @test
     */
    public function it_has_a_fallback_to_locality_when_full_address_has_null_coordinates(): void
    {
        $id = 'b9ec8a0a-ec9d-4dd3-9aaa-6d5b41b69d7c';

        $address = new Address(
            new Street('Wetstraat 1 (foutief)'),
            new PostalCode('1000'),
            new Locality('Bxl'),
            new CountryCode('BE')
        );

        $placeCreated = new PlaceCreated(
            $id,
            new Language('en'),
            new Title('Some place'),
            new EventType('01.01', 'Some category'),
            $address,
            new Calendar(CalendarType::PERMANENT())
        );

        $command = new UpdateGeoCoordinatesFromAddress($id, $address);

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

        $expectedEvent = new GeoCoordinatesUpdated($id, $coordinates);

        $this->scenario
            ->withAggregateId($id)
            ->given([$placeCreated])
            ->when($command)
            ->then([$expectedEvent]);
    }
}
