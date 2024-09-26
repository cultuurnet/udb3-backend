<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use CultuurNet\UDB3\Cdb\EventItemFactory;
use CultuurNet\UDB3\Event\Commands\UpdateGeoCoordinatesFromAddress;
use CultuurNet\UDB3\Event\Events\EventImportedFromUDB2;
use CultuurNet\UDB3\Event\Events\EventUpdatedFromUDB2;
use CultuurNet\UDB3\Offer\AbstractGeoCoordinatesProcessManager;

class GeoCoordinatesProcessManager extends AbstractGeoCoordinatesProcessManager
{
    protected function getEventHandlers(): array
    {
        return [
            EventImportedFromUDB2::class => 'handleEventImportedFromUDB2',
            EventUpdatedFromUDB2::class => 'handleEventUpdatedFromUDB2',
        ];
    }

    /**
     * @throws \CultureFeed_Cdb_ParseException
     */
    protected function handleEventImportedFromUDB2(
        EventImportedFromUDB2 $eventImportedFromUDB2
    ): void {
        $this->dispatchCommand(
            $eventImportedFromUDB2->getCdbXmlNamespaceUri(),
            $eventImportedFromUDB2->getCdbXml(),
            $eventImportedFromUDB2->getEventId()
        );
    }

    /**
     * @throws \CultureFeed_Cdb_ParseException
     */
    protected function handleEventUpdatedFromUDB2(
        EventUpdatedFromUDB2 $eventUpdatedFromUDB2
    ): void {
        $this->dispatchCommand(
            $eventUpdatedFromUDB2->getCdbXmlNamespaceUri(),
            $eventUpdatedFromUDB2->getCdbXml(),
            $eventUpdatedFromUDB2->getEventId()
        );
    }

    /**
     * @throws \CultureFeed_Cdb_ParseException
     */
    private function dispatchCommand(
        string $cdbXmlNamespaceUri,
        string $cdbXml,
        string $eventId
    ): void {
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
            $address->toUdb3ModelAddress()
        );

        $this->commandBus->dispatch($command);
    }
}
