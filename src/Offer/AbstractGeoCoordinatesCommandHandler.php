<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use Broadway\Repository\Repository;
use CultuurNet\UDB3\Geocoding\EnrichedCachedGeocodingService;
use CultuurNet\UDB3\Address\AddressFormatter;
use CultuurNet\UDB3\CommandHandling\Udb3CommandHandler;
use CultuurNet\UDB3\Geocoding\HasCoordinates;
use CultuurNet\UDB3\Offer\Commands\AbstractUpdateGeoCoordinatesFromAddress;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use JsonException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

abstract class AbstractGeoCoordinatesCommandHandler extends Udb3CommandHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private Repository $offerRepository;

    private AddressFormatter $defaultAddressFormatter;

    private AddressFormatter $fallbackAddressFormatter;
    private HasCoordinates $geocodingCoordinatesService;
    private EnrichedCachedGeocodingService $enrichedCachedGeocodingService;
    private DocumentRepository $documentRepository;
    private bool $addressEnrichment;

    public function __construct(
        Repository $placeRepository,
        AddressFormatter $defaultAddressFormatter,
        AddressFormatter $fallbackAddressFormatter,
        HasCoordinates $geocodingService,
        EnrichedCachedGeocodingService $enrichedCachedGeocodingService,
        DocumentRepository $documentRepository,
        bool $addressEnrichment
    ) {
        $this->offerRepository = $placeRepository;
        $this->defaultAddressFormatter = $defaultAddressFormatter;
        $this->fallbackAddressFormatter = $fallbackAddressFormatter;
        $this->geocodingCoordinatesService = $geocodingService;
        $this->enrichedCachedGeocodingService = $enrichedCachedGeocodingService;
        $this->logger = new NullLogger();
        $this->documentRepository = $documentRepository;
        $this->addressEnrichment = $addressEnrichment;
    }

    protected function updateGeoCoordinatesFromAddress(
        AbstractUpdateGeoCoordinatesFromAddress $updateGeoCoordinates
    ): void {
        $offerId = $updateGeoCoordinates->getItemId();

        $exactAddress = $this->defaultAddressFormatter->format(
            $updateGeoCoordinates->getAddress()
        );

        $locationName = $this->fetchOfferName($offerId);

        $coordinates = $this->geocodingCoordinatesService->getCoordinates($exactAddress, $locationName);

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

            $coordinates = $this->geocodingCoordinatesService->getCoordinates($fallbackAddress, $locationName);

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

        $this->enrichedCachedGeocodingService->saveEnrichedAddress($exactAddress, $locationName);

        /** @var Offer $offer */
        $offer = $this->offerRepository->load($offerId);
        $offer->updateGeoCoordinates($coordinates);
        $this->offerRepository->save($offer);
    }

    private function fetchOfferName(string $offerId): ?string
    {
        if (! $this->addressEnrichment) {
            return null;
        }

        try {
            $offer = $this->documentRepository->fetch($offerId)->getAssocBody();

            return $offer['name']['nl'] ?? $offer['name']['fr'];
        } catch (DocumentDoesNotExist|JsonException $e) {
            return null;
        }
    }
}
