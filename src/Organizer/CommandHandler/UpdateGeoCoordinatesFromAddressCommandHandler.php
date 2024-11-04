<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use CultuurNet\UDB3\Address\Address as LegacyAddress;
use CultuurNet\UDB3\Address\Formatter\AddressFormatter;
use CultuurNet\UDB3\CommandHandling\Udb3CommandHandler;
use CultuurNet\UDB3\Geocoding\GeocodingService;
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
        $coordinates = $this->geocodingService->getCoordinates(
            $this->defaultAddressFormatter->format(
                LegacyAddress::fromUdb3ModelAddress($updateGeoCoordinates->address())
            )
        );

        if ($coordinates === null) {
            $coordinates = $this->geocodingService->getCoordinates(
                $this->fallbackAddressFormatter->format(
                    LegacyAddress::fromUdb3ModelAddress($updateGeoCoordinates->address())
                )
            );
        }

        if ($coordinates === null) {
            return;
        }

        $organizer = $this->loadOrganizer($updateGeoCoordinates->organizerId());
        $organizer->updateGeoCoordinates($coordinates);
        $this->organizerRepository->save($organizer);
    }

    protected function loadOrganizer(string $id): Organizer
    {
        return $this->organizerRepository->load($id);
    }
}
