<?php

namespace CultuurNet\UDB3\Event;

use CultuurNet\UDB3\Cdb\EventItemFactory;
use CultuurNet\UDB3\Event\Commands\UpdateGeoCoordinatesFromAddress;
use CultuurNet\UDB3\Event\Events\EventImportedFromUDB2;
use CultuurNet\UDB3\Event\Events\EventUpdatedFromUDB2;
use CultuurNet\UDB3\Offer\AbstractGeoCoordinatesProcessManager;

class GeoCoordinatesProcessManager extends AbstractGeoCoordinatesProcessManager
{
    /**
     * @return array
     */
    protected function getEventHandlers()
    {
        return [
            EventImportedFromUDB2::class => 'handleEventImportedFromUDB2',
            EventUpdatedFromUDB2::class => 'handleEventUpdatedFromUDB2',
        ];
    }

    /**
     * @param EventImportedFromUDB2 $eventImportedFromUDB2
     * @throws \CultureFeed_Cdb_ParseException
     */
    protected function handleEventImportedFromUDB2(
        EventImportedFromUDB2 $eventImportedFromUDB2
    ) {
        $this->dispatchCommand(
            $eventImportedFromUDB2->getCdbXmlNamespaceUri(),
            $eventImportedFromUDB2->getCdbXml(),
            $eventImportedFromUDB2->getEventId()
        );
    }

    /**
     * @param EventUpdatedFromUDB2 $eventUpdatedFromUDB2
     * @throws \CultureFeed_Cdb_ParseException
     */
    protected function handleEventUpdatedFromUDB2(
        EventUpdatedFromUDB2 $eventUpdatedFromUDB2
    ) {
        $this->dispatchCommand(
            $eventUpdatedFromUDB2->getCdbXmlNamespaceUri(),
            $eventUpdatedFromUDB2->getCdbXml(),
            $eventUpdatedFromUDB2->getEventId()
        );
    }

    /**
     * @param string $cdbXmlNamespaceUri
     * @param string $cdbXml
     * @param string $eventId
     * @throws \CultureFeed_Cdb_ParseException
     */
    private function dispatchCommand(
        $cdbXmlNamespaceUri,
        $cdbXml,
        $eventId
    ) {
        $event = EventItemFactory::createEventFromCdbXml(
            $cdbXmlNamespaceUri,
            $cdbXml
        );

        // Location is required, else the create would fail.
        // This location needs to be a dummy location.
        // A dummy location has no cdbid and no external id.
        $location = $event->getLocation();
        if ($location->getCdbid() || $location->getExternalId()) {
            return;
        }

        // Address is required, else the create would fail.
        $physicalAddress = $location->getAddress()->getPhysicalAddress();
        if (!$physicalAddress) {
            return;
        }

        // Address is always valid, else the create would fail.
        $address = $this->addressFactory->fromCdbAddress($physicalAddress);

        $command = new UpdateGeoCoordinatesFromAddress(
            $eventId,
            $address
        );

        $this->commandBus->dispatch($command);
    }
}
