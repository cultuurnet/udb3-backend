<?php

namespace CultuurNet\UDB3\Event;

use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBusInterface;
use Broadway\EventStore\EventStoreInterface;
use CultuurNet\Geocoding\Coordinate\Coordinates;
use CultuurNet\Geocoding\Coordinate\Latitude;
use CultuurNet\Geocoding\Coordinate\Longitude;
use CultuurNet\Geocoding\GeocodingServiceInterface;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\AddressFormatterInterface;
use CultuurNet\UDB3\Address\DefaultAddressFormatter;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\LocalityAddressFormatter;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Event\Commands\UpdateGeoCoordinatesFromAddress;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\GeoCoordinatesUpdated;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\MockObject\MockObject;
use ValueObjects\Geography\Country;

class GeoCoordinatesCommandHandlerTest extends CommandHandlerScenarioTestCase
{
    /**
     * @var AddressFormatterInterface
     */
    private $defaultAddressFormatter;

    /**
     * @var AddressFormatterInterface
     */
    private $localityAddressFormatter;

    /**
     * @var GeocodingServiceInterface|MockObject
     */
    private $geocodingService;

    /**
     * @param EventStoreInterface $eventStore
     * @param EventBusInterface $eventBus
     * @return GeoCoordinatesCommandHandler
     */
    protected function createCommandHandler(EventStoreInterface $eventStore, EventBusInterface $eventBus)
    {
        $eventRepository = new EventRepository(
            $eventStore,
            $eventBus
        );

        $this->defaultAddressFormatter = new DefaultAddressFormatter();
        $this->localityAddressFormatter = new LocalityAddressFormatter();

        $this->geocodingService = $this->createMock(GeocodingServiceInterface::class);

        return new GeoCoordinatesCommandHandler(
            $eventRepository,
            $this->defaultAddressFormatter,
            $this->localityAddressFormatter,
            $this->geocodingService
        );
    }

    /**
     * @test
     */
    public function it_creates_coordinates_from_an_address_and_updates_them_on_the_given_place()
    {
        $eventId = 'b9ec8a0a-ec9d-4dd3-9aaa-6d5b41b69d7c';

        $address = new Address(
            new Street('Wetstraat 1'),
            new PostalCode('1000'),
            new Locality('Bxl'),
            Country::fromNative('BE')
        );

        $eventCreated = new EventCreated(
            $eventId,
            new Language('en'),
            new Title('Faith no More'),
            new EventType('0.50.4.0.0', 'Concert'),
            new LocationId('7a59de16-6111-4658-aa6e-958ff855d14e'),
            new Calendar(CalendarType::PERMANENT()),
            new Theme('1.8.1.0.0', 'Rock')
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
            ->given([$eventCreated])
            ->when($command)
            ->then([$expectedEvent]);
    }

    /**
     * @test
     */
    public function it_has_a_fallback_to_locality_when_full_address_has_null_coordinates()
    {
        $eventId = 'b9ec8a0a-ec9d-4dd3-9aaa-6d5b41b69d7c';

        $address = new Address(
            new Street('Wetstraat 1 (foutief)'),
            new PostalCode('1000'),
            new Locality('Bxl'),
            Country::fromNative('BE')
        );

        $eventCreated = new EventCreated(
            $eventId,
            new Language('en'),
            new Title('Faith no More'),
            new EventType('0.50.4.0.0', 'Concert'),
            new LocationId('7a59de16-6111-4658-aa6e-958ff855d14e'),
            new Calendar(CalendarType::PERMANENT()),
            new Theme('1.8.1.0.0', 'Rock')
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
            ->given([$eventCreated])
            ->when($command)
            ->then([$expectedEvent]);
    }


    /**
     * @test
     */
    public function it_skips_update_if_the_geo_coordinates_can_not_be_resolved()
    {
        $eventId = 'b9ec8a0a-ec9d-4dd3-9aaa-6d5b41b69d7c';

        $address = new Address(
            new Street('Wetstraat 1'),
            new PostalCode('1000'),
            new Locality('Bxl'),
            Country::fromNative('BE')
        );

        $eventCreated = new EventCreated(
            $eventId,
            new Language('en'),
            new Title('Faith no More'),
            new EventType('0.50.4.0.0', 'Concert'),
            new LocationId('7a59de16-6111-4658-aa6e-958ff855d14e'),
            new Calendar(CalendarType::PERMANENT()),
            new Theme('1.8.1.0.0', 'Rock')
        );

        $command = new UpdateGeoCoordinatesFromAddress($eventId, $address);

        $this->geocodingService->expects($this->any())
            ->method('getCoordinates')
            ->willReturn(null);

        $this->scenario
            ->withAggregateId($eventId)
            ->given([$eventCreated])
            ->when($command)
            ->then([]);
    }
}
