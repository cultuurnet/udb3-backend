<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Geocoding\GeocodingService;
use CultuurNet\UDB3\Address\AddressFormatter;
use CultuurNet\UDB3\CommandHandling\Udb3CommandHandler;
use CultuurNet\UDB3\Organizer\Commands\UpdateGeoCoordinatesFromAddress;
use CultuurNet\UDB3\Organizer\Organizer;
use CultuurNet\UDB3\Organizer\OrganizerRepository;

class UpdateGeoCoordinatesFromAddressCommandHandler extends Udb3CommandHandler
{
    private OrganizerRepository $organizerRepository;

    private AddressFormatter $defaultAddressFormatter;

    private AddressFormatter $fallbackAddressFormatter;

    private GeocodingService $geocodingService;

    public function __construct(
        OrganizerRepository $organizerRepository,
        AddressFormatter $defaultAddressFormatter,
        AddressFormatter $fallbackAddressFormatter,
        GeocodingService $geocodingService
    ) {
        $this->organizerRepository = $organizerRepository;
        $this->defaultAddressFormatter = $defaultAddressFormatter;
        $this->fallbackAddressFormatter = $fallbackAddressFormatter;
        $this->geocodingService = $geocodingService;
    }

    protected function handleUpdateGeoCoordinatesFromAddress(UpdateGeoCoordinatesFromAddress $updateGeoCoordinates): void
    {
        $enrichedAddress = $this->geocodingService->fetchAddress(
            $this->defaultAddressFormatter->format(
                $updateGeoCoordinates->address()
            )
        );

        if ($enrichedAddress === null) {
            $enrichedAddress = $this->geocodingService->fetchAddress(
                $this->fallbackAddressFormatter->format(
                    $updateGeoCoordinates->address()
                )
            );
        }

        if ($enrichedAddress === null) {
            return;
        }

        $organizer = $this->loadOrganizer($updateGeoCoordinates->organizerId());
        $organizer->updateGeoCoordinates(Coordinates::fromLocation($enrichedAddress));
        $this->organizerRepository->save($organizer);
    }

    protected function loadOrganizer(string $id): Organizer
    {
        return $this->organizerRepository->load($id);
    }
}
