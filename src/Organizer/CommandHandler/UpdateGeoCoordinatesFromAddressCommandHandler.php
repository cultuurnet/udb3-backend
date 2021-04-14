<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use CultuurNet\UDB3\Geocoding\GeocodingService;
use CultuurNet\UDB3\Address\AddressFormatterInterface;
use CultuurNet\UDB3\CommandHandling\Udb3CommandHandler;
use CultuurNet\UDB3\Organizer\Commands\UpdateGeoCoordinatesFromAddress;
use CultuurNet\UDB3\Organizer\Organizer;
use CultuurNet\UDB3\Organizer\OrganizerRepository;

class UpdateGeoCoordinatesFromAddressCommandHandler extends Udb3CommandHandler
{
    /**
     * @var OrganizerRepository
     */
    private $organizerRepository;

    /**
     * @var AddressFormatterInterface
     */
    private $defaultAddressFormatter;

    /**
     * @var AddressFormatterInterface
     */
    private $fallbackAddressFormatter;

    /**
     * @var GeocodingService
     */
    private $geocodingService;


    public function __construct(
        OrganizerRepository $organizerRepository,
        AddressFormatterInterface $defaultAddressFormatter,
        AddressFormatterInterface $fallbackAddressFormatter,
        GeocodingService $geocodingService
    ) {
        $this->organizerRepository = $organizerRepository;
        $this->defaultAddressFormatter = $defaultAddressFormatter;
        $this->fallbackAddressFormatter = $fallbackAddressFormatter;
        $this->geocodingService = $geocodingService;
    }


    protected function handleUpdateGeoCoordinatesFromAddress(UpdateGeoCoordinatesFromAddress $updateGeoCoordinates)
    {
        $coordinates = $this->geocodingService->getCoordinates(
            $this->defaultAddressFormatter->format(
                $updateGeoCoordinates->address()
            )
        );

        if ($coordinates === null) {
            $coordinates = $this->geocodingService->getCoordinates(
                $this->fallbackAddressFormatter->format(
                    $updateGeoCoordinates->address()
                )
            );
        }

        if ($coordinates === null) {
            return;
        }

        /** @var Organizer $organizer */
        $organizer = $this->loadOrganizer($updateGeoCoordinates->organizerId());
        $organizer->updateGeoCoordinates($coordinates);
        $this->organizerRepository->save($organizer);
    }

    protected function loadOrganizer(string $id)
    {
        return $this->organizerRepository->load($id);
    }
}
