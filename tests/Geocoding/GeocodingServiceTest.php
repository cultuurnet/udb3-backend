<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Geocoding;

use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Geocoding\Coordinate\Latitude;
use CultuurNet\UDB3\Geocoding\Coordinate\Longitude;
use CultuurNet\UDB3\Geocoding\Exception\NoGoogleAddressReceived;
use Geocoder\Geocoder;
use Geocoder\Model\Address;
use Geocoder\Model\AddressCollection;
use Geocoder\Model\AdminLevelCollection;
use Geocoder\Model\Coordinates as GeocoderCoordinates;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class GeocodingServiceTest extends TestCase
{
    /**
     * @var Geocoder|MockObject
     */
    private $geocoder;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    private GeocodingService $service;

    public function setUp(): void
    {
        $this->geocoder = $this->createMock(GeocodingCacheFacade::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new GeocodingService($this->geocoder, $this->logger);
    }

    /**
     * @test
     */
    public function it_returns_coordinates(): void
    {
        $address = 'Wetstraat 1, 1000 Brussel, BE';

        $latFloat = 1.07845;
        $longFloat = 2.76412;

        $result = new AddressCollection([
            new Address(
                'google',
                new AdminLevelCollection(),
                new GeocoderCoordinates($latFloat, $longFloat)
            ),
        ]);

        $expectedCoordinates = new Coordinates(
            new Latitude($latFloat),
            new Longitude($longFloat)
        );

        $this->geocoder->expects($this->once())
            ->method('fetchCoordinates')
            ->with($address)
            ->willReturn($expectedCoordinates);

        $actualCoordinates = $this->service->getCoordinates($address);

        $this->assertEquals($expectedCoordinates, $actualCoordinates);
    }

    /**
     * @test
     */
    public function it_returns_null_on_no_result_exception_from_geocoder(): void
    {
        $address = 'Eikelberg (achter de bibliotheek), 8340 Sijsele (Damme), BE';

        $this->geocoder->expects($this->once())
            ->method('fetchCoordinates')
            ->with($address)
            ->willThrowException(
                new NoGoogleAddressReceived()
            );

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('No results for address: "' . $address . '". Exception message: ' . NoGoogleAddressReceived::ERROR);

        $actualCoordinates = $this->service->getCoordinates($address);

        $this->assertNull($actualCoordinates);
    }
}
