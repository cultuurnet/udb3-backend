<?php

namespace CultuurNet\UDB3\Offer;

use Broadway\Repository\RepositoryInterface;
use CultuurNet\Geocoding\GeocodingServiceInterface;
use CultuurNet\UDB3\Address\AddressFormatterInterface;
use CultuurNet\UDB3\CommandHandling\Udb3CommandHandler;
use CultuurNet\UDB3\Offer\Commands\AbstractUpdateGeoCoordinatesFromAddress;

abstract class AbstractGeoCoordinatesCommandHandler extends Udb3CommandHandler
{
    /**
     * @var RepositoryInterface
     */
    private $offerRepository;

    /**
     * @var AddressFormatterInterface
     */
    private $defaultAddressFormatter;

    /**
     * @var AddressFormatterInterface
     */
    private $fallbackAddressFormatter;

    /**
     * @var GeocodingServiceInterface
     */
    private $geocodingService;

    /**
     * @param RepositoryInterface $placeRepository
     * @param AddressFormatterInterface $defaultAddressFormatter
     * @param AddressFormatterInterface $fallbackAddressFormatter
     * @param GeocodingServiceInterface $geocodingService
     */
    public function __construct(
        RepositoryInterface $placeRepository,
        AddressFormatterInterface $defaultAddressFormatter,
        AddressFormatterInterface $fallbackAddressFormatter,
        GeocodingServiceInterface $geocodingService
    ) {
        $this->offerRepository = $placeRepository;
        $this->defaultAddressFormatter = $defaultAddressFormatter;
        $this->fallbackAddressFormatter = $fallbackAddressFormatter;
        $this->geocodingService = $geocodingService;
    }

    /**
     * @param AbstractUpdateGeoCoordinatesFromAddress $updateGeoCoordinates
     */
    protected function updateGeoCoordinatesFromAddress(AbstractUpdateGeoCoordinatesFromAddress $updateGeoCoordinates)
    {
        $coordinates = $this->geocodingService->getCoordinates(
            $this->defaultAddressFormatter->format(
                $updateGeoCoordinates->getAddress()
            )
        );

        if ($coordinates === null) {
            $coordinates = $this->geocodingService->getCoordinates(
                $this->fallbackAddressFormatter->format(
                    $updateGeoCoordinates->getAddress()
                )
            );
        }

        if ($coordinates === null) {
            return;
        }

        /** @var Offer $offer */
        $offer = $this->offerRepository->load($updateGeoCoordinates->getItemId());
        $offer->updateGeoCoordinates($coordinates);
        $this->offerRepository->save($offer);
    }
}
