<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use Broadway\Repository\Repository;
use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Geocoding\Coordinate\Latitude;
use CultuurNet\UDB3\Geocoding\Coordinate\Longitude;
use CultuurNet\UDB3\Geocoding\GeocodingService;
use CultuurNet\UDB3\Address\AddressFormatter;
use CultuurNet\UDB3\CommandHandling\Udb3CommandHandler;
use CultuurNet\UDB3\Offer\Commands\AbstractUpdateGeoCoordinatesFromAddress;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

abstract class AbstractGeoCoordinatesCommandHandler extends Udb3CommandHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private Repository $offerRepository;

    private AddressFormatter $defaultAddressFormatter;

    private AddressFormatter $fallbackAddressFormatter;

    private GeocodingService $geocodingService;

    public function __construct(
        Repository $placeRepository,
        AddressFormatter $defaultAddressFormatter,
        AddressFormatter $fallbackAddressFormatter,
        GeocodingService $geocodingService
    ) {
        $this->offerRepository = $placeRepository;
        $this->defaultAddressFormatter = $defaultAddressFormatter;
        $this->fallbackAddressFormatter = $fallbackAddressFormatter;
        $this->geocodingService = $geocodingService;
        $this->logger = new NullLogger();
    }

    protected function updateGeoCoordinatesFromAddress(
        AbstractUpdateGeoCoordinatesFromAddress $updateGeoCoordinates
    ): void {
        $offerId = $updateGeoCoordinates->getItemId();

        $exactAddress = $this->defaultAddressFormatter->format(
            $updateGeoCoordinates->getAddress()
        );

        $enrichedAddress = $this->geocodingService->fetchAddress($exactAddress);

        if ($enrichedAddress !== null && $enrichedAddress->getCoordinates() === null) {
            $fallbackAddress = $this->fallbackAddressFormatter->format(
                $updateGeoCoordinates->getAddress()
            );

            $this->logger->debug(
                sprintf(
                    "Could not find coordinates for exact address '%s', trying '%s' instead for offer id %s.",
                    $exactAddress,
                    $fallbackAddress,
                    $offerId
                )
            );

            $enrichedAddress = $this->geocodingService->fetchAddress($fallbackAddress);

            if ($enrichedAddress !== null && $enrichedAddress->getCoordinates() === null) {
                $this->logger->debug(
                    "Found coordinates for fallback address '$fallbackAddress' for offer id $offerId"
                );
            }
        }

        if ($enrichedAddress === null || $enrichedAddress->getCoordinates() === null) {
            $this->logger->debug('Could not find coordinates for fallback address for offer id ' . $offerId);
            return;
        }

        /** @var Offer $offer */
        $offer = $this->offerRepository->load($offerId);
        $offer->updateGeoCoordinates(Coordinates::fromLocation($enrichedAddress));
        $this->offerRepository->save($offer);
    }
}
