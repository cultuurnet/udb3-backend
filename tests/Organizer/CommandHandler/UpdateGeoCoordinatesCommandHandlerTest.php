<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Address\Formatter\FullAddressFormatter;
use CultuurNet\UDB3\Address\Formatter\LocalityAddressFormatter;
use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Geocoding\Coordinate\Latitude;
use CultuurNet\UDB3\Geocoding\Coordinate\Longitude;
use CultuurNet\UDB3\Geocoding\GeocodingService;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
use CultuurNet\UDB3\Organizer\Commands\UpdateGeoCoordinatesFromAddress;
use CultuurNet\UDB3\Organizer\Events\GeoCoordinatesUpdated;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreated;
use CultuurNet\UDB3\Organizer\OrganizerRepository;
use PHPUnit\Framework\MockObject\MockObject;

class UpdateGeoCoordinatesCommandHandlerTest extends CommandHandlerScenarioTestCase
{
    /**
     * @var GeocodingService&MockObject
     */
    private $geocodingService;

    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): UpdateGeoCoordinatesFromAddressCommandHandler
    {
        $this->geocodingService = $this->createMock(GeocodingService::class);

        return new UpdateGeoCoordinatesFromAddressCommandHandler(
            new OrganizerRepository($eventStore, $eventBus),
            new FullAddressFormatter(),
            new LocalityAddressFormatter(),
            $this->geocodingService
        );
    }

    /**
     * @test
     */
    public function it_creates_coordinates_from_an_address_and_updates_them_on_the_given_place(): void
    {
        $organizerId = $this->aUuid();
        $address = $this->anAddress();

        $organizerCreated = new OrganizerCreated(
            $organizerId,
            'some representative title',
            $address->getStreet()->toString(),
            $address->getPostalCode()->toString(),
            $address->getLocality()->toString(),
            $address->getCountryCode()->toString(),
            ['050/123'],
            ['test@test.be', 'test2@test.be'],
            ['http://www.google.be']
        );

        $command = new UpdateGeoCoordinatesFromAddress($organizerId, $address);

        $coordinates = $this->someCoordinates();

        $this->geocodingService->expects($this->once())
            ->method('getCoordinates')
            ->with('Wetstraat 1, 1000 Bxl, BE')
            ->willReturn($coordinates);

        $expectedEvent = new GeoCoordinatesUpdated(
            $organizerId,
            $coordinates->getLatitude()->toFloat(),
            $coordinates->getLongitude()->toFloat()
        );

        $this->scenario
            ->withAggregateId($organizerId)
            ->given([$organizerCreated])
            ->when($command)
            ->then([$expectedEvent]);
    }

    /**
     * @test
     */
    public function it_has_a_fallback_to_locality_when_full_address_has_null_coordinates(): void
    {
        $organizerId = $this->aUuid();
        $address = $this->anAddress();

        $organizerCreated = new OrganizerCreated(
            $organizerId,
            'some representative title',
            $address->getStreet()->toString(),
            $address->getPostalCode()->toString(),
            $address->getLocality()->toString(),
            $address->getCountryCode()->toString(),
            ['050/123'],
            ['test@test.be', 'test2@test.be'],
            ['http://www.google.be']
        );

        $command = new UpdateGeoCoordinatesFromAddress($organizerId, $address);

        $coordinates = $this->someCoordinates();

        $this->geocodingService->expects($this->exactly(2))
            ->method('getCoordinates')
            ->withConsecutive(
                [
                    'Wetstraat 1, 1000 Bxl, BE',
                ],
                [
                    '1000 Bxl, BE',
                ]
            )
            ->willReturnOnConsecutiveCalls(null, $coordinates);

        $expectedEvent = new GeoCoordinatesUpdated(
            $organizerId,
            $coordinates->getLatitude()->toFloat(),
            $coordinates->getLongitude()->toFloat()
        );

        $this->scenario
            ->withAggregateId($organizerId)
            ->given([$organizerCreated])
            ->when($command)
            ->then([$expectedEvent]);
    }

    /**
     * @test
     */
    public function it_skips_update_if_the_geo_coordinates_can_not_be_resolved(): void
    {
        $organizerId = $this->aUuid();
        $address = $this->anAddress();

        $organizerCreated = new OrganizerCreated(
            $organizerId,
            'some representative title',
            $address->getStreet()->toString(),
            $address->getPostalCode()->toString(),
            $address->getLocality()->toString(),
            $address->getCountryCode()->toString(),
            ['050/123'],
            ['test@test.be', 'test2@test.be'],
            ['http://www.google.be'],
        );

        $command = new UpdateGeoCoordinatesFromAddress($organizerId, $address);

        $this->geocodingService->expects($this->any())
            ->method('getCoordinates')
            ->willReturnOnConsecutiveCalls(null);

        $this->scenario
            ->withAggregateId($organizerId)
            ->given([$organizerCreated])
            ->when($command)
            ->then([]);
    }

    public function aUuid(): string
    {
        return 'b9ec8a0a-ec9d-4dd3-9aaa-6d5b41b69d7c';
    }

    public function anAddress(): Address
    {
        return new Address(
            new Street('Wetstraat 1'),
            new PostalCode('1000'),
            new Locality('Bxl'),
            new CountryCode('BE')
        );
    }

    public function someCoordinates(): Coordinates
    {
        return new Coordinates(
            new Latitude(-0.12),
            new Longitude(4.76)
        );
    }
}
