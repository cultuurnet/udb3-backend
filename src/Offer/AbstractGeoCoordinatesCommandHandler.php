<?php

namespace CultuurNet\UDB3\Offer;

use Broadway\Repository\RepositoryInterface;
use CultuurNet\UDB3\Geocoding\GeocodingServiceInterface;
use CultuurNet\UDB3\Address\AddressFormatterInterface;
use CultuurNet\UDB3\CommandHandling\Udb3CommandHandler;
use CultuurNet\UDB3\Offer\Commands\AbstractUpdateGeoCoordinatesFromAddress;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

abstract class AbstractGeoCoordinatesCommandHandler extends Udb3CommandHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

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
        $this->logger = new NullLogger();
    }

    /**
     * @param AbstractUpdateGeoCoordinatesFromAddress $updateGeoCoordinates
     */
    protected function updateGeoCoordinatesFromAddress(AbstractUpdateGeoCoordinatesFromAddress $updateGeoCoordinates)
    {
        $offerId = $updateGeoCoordinates->getItemId();

        $exactAddress = $this->defaultAddressFormatter->format(
            $updateGeoCoordinates->getAddress()
        );

        $coordinates = $this->geocodingService->getCoordinates($exactAddress);

        if ($coordinates === null) {
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

            $coordinates = $this->geocodingService->getCoordinates($fallbackAddress);

            if (!is_null($coordinates)) {
                $this->logger->debug(
                    "Found coordinates for fallback address '$fallbackAddress' for offer id $offerId"
                );
            }
        }

        if ($coordinates === null) {
            $this->logger->debug('Could not find coordinates for fallback address for offer id ' . $offerId);
            return;
        }

        /** @var Offer $offer */
        $offer = $this->offerRepository->load($offerId);
        $offer->updateGeoCoordinates($coordinates);
        $this->offerRepository->save($offer);
    }
}
