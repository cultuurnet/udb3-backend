<?php

namespace CultuurNet\UDB3\Place;

use CultureFeed_Cdb_Data_Address;
use CultuurNet\UDB3\Actor\ActorImportedFromUDB2;
use CultuurNet\UDB3\Cdb\ActorItemFactory;
use CultuurNet\UDB3\Offer\AbstractGeoCoordinatesProcessManager;
use CultuurNet\UDB3\Place\Commands\UpdateGeoCoordinatesFromAddress;
use CultuurNet\UDB3\Place\Events\AddressUpdated;
use CultuurNet\UDB3\Place\Events\MajorInfoUpdated;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Place\Events\PlaceImportedFromUDB2;
use CultuurNet\UDB3\Place\Events\PlaceUpdatedFromUDB2;

class GeoCoordinatesProcessManager extends AbstractGeoCoordinatesProcessManager
{
    /**
     * @return array
     */
    protected function getEventHandlers()
    {
        return [
            PlaceCreated::class => 'handlePlaceCreated',
            MajorInfoUpdated::class => 'handleMajorInfoUpdated',
            AddressUpdated::class => 'handleAddressUpdated',
            PlaceImportedFromUDB2::class => 'handleActorImportedFromUDB2',
            PlaceUpdatedFromUDB2::class => 'handleActorImportedFromUDB2',
        ];
    }

    /**
     * @param PlaceCreated $placeCreated
     */
    protected function handlePlaceCreated(PlaceCreated $placeCreated)
    {
        $command = new UpdateGeoCoordinatesFromAddress(
            $placeCreated->getPlaceId(),
            $placeCreated->getAddress()
        );

        $this->commandBus->dispatch($command);
    }

    /**
     * @param MajorInfoUpdated $majorInfoUpdated
     */
    protected function handleMajorInfoUpdated(MajorInfoUpdated $majorInfoUpdated)
    {
        // We don't know if the address has actually been updated because
        // MajorInfoUpdated is too coarse, but if we use the cached geocoding
        // service we won't be wasting much resources when using a naive
        // approach like this.
        $command = new UpdateGeoCoordinatesFromAddress(
            $majorInfoUpdated->getPlaceId(),
            $majorInfoUpdated->getAddress()
        );

        $this->commandBus->dispatch($command);
    }

    /**
     * @param AddressUpdated $addressUpdated
     */
    protected function handleAddressUpdated(AddressUpdated $addressUpdated)
    {
        $command = new UpdateGeoCoordinatesFromAddress(
            $addressUpdated->getPlaceId(),
            $addressUpdated->getAddress()
        );

        $this->commandBus->dispatch($command);
    }

    /**
     * @param ActorImportedFromUDB2 $actorImportedFromUDB2
     * @throws \CultureFeed_Cdb_ParseException
     */
    protected function handleActorImportedFromUDB2(ActorImportedFromUDB2 $actorImportedFromUDB2)
    {
        $actor = ActorItemFactory::createActorFromCdbXml(
            $actorImportedFromUDB2->getCdbXmlNamespaceUri(),
            $actorImportedFromUDB2->getCdbXml()
        );

        $contactInfo = $actor->getContactInfo();

        // Do nothing if no contact info is found.
        if (!$contactInfo) {
            return;
        }

        // Get all physical locations from the list of addresses.
        $addresses = array_map(
            function (CultureFeed_Cdb_Data_Address $address) {
                return $address->getPhysicalAddress();
            },
            $contactInfo->getAddresses()
        );

        // Filter out addresses without physical location.
        $addresses = array_filter($addresses);

        // Do nothing if no address is found.
        if (empty($addresses)) {
            return;
        }

        /* @var \CultureFeed_Cdb_Data_Address_PhysicalAddress $cdbAddress */
        $cdbAddress = $addresses[0];

        try {
            // Convert the cdbxml address to a udb3 address.
            $address = $this->addressFactory->fromCdbAddress($cdbAddress);
        } catch (\InvalidArgumentException $e) {
            // If conversion failed, log an error and do nothing.
            $this->logger->error(
                'Could not convert a cdbxml address to a udb3 address for geocoding.',
                [
                    'placeId' => $actorImportedFromUDB2->getActorId(),
                    'error' => $e->getMessage(),
                ]
            );
            return;
        }

        // We don't know if the address has actually been updated because
        // ActorImportedFromUDB2 is too coarse, but if we use the cached
        // geocoding service we won't be wasting much resources when using
        // a naive approach like this.
        $command = new UpdateGeoCoordinatesFromAddress(
            $actorImportedFromUDB2->getActorId(),
            $address
        );

        $this->commandBus->dispatch($command);
    }
}
