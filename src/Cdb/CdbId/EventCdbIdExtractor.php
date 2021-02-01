<?php

namespace CultuurNet\UDB3\Cdb\CdbId;

use CultuurNet\UDB3\Cdb\ExternalId\ArrayMappingService;
use CultuurNet\UDB3\Cdb\ExternalId\MappingServiceInterface;

class EventCdbIdExtractor implements EventCdbIdExtractorInterface
{
    /**
     * @var MappingServiceInterface
     */
    private $placeExternalIdMappingService;

    /**
     * @var MappingServiceInterface
     */
    private $organizerExternalIdMappingService;

    /**
     * @param MappingServiceInterface|null $placeExternalIdMappingService
     * @param MappingServiceInterface $organizerExternalIdMappingService
     */
    public function __construct(
        MappingServiceInterface $placeExternalIdMappingService = null,
        MappingServiceInterface $organizerExternalIdMappingService = null
    ) {
        if (is_null($placeExternalIdMappingService)) {
            $placeExternalIdMappingService = new ArrayMappingService([]);
        }
        if (is_null($organizerExternalIdMappingService)) {
            $organizerExternalIdMappingService = new ArrayMappingService([]);
        }

        $this->placeExternalIdMappingService = $placeExternalIdMappingService;
        $this->organizerExternalIdMappingService = $organizerExternalIdMappingService;
    }

    /**
     * @param \CultureFeed_Cdb_Item_Event $cdbEvent
     * @return string|null
     */
    public function getRelatedPlaceCdbId(\CultureFeed_Cdb_Item_Event $cdbEvent)
    {
        $cdbPlace = $cdbEvent->getLocation();

        if (!is_null($cdbPlace)) {
            return $this->getCdbIdFromEmbeddedLocationOrOrganizer(
                $cdbPlace,
                $this->placeExternalIdMappingService
            );
        } else {
            return null;
        }
    }

    /**
     * @param \CultureFeed_Cdb_Item_Event $cdbEvent
     * @return string|null
     */
    public function getRelatedOrganizerCdbId(\CultureFeed_Cdb_Item_Event $cdbEvent)
    {
        $cdbOrganizer = $cdbEvent->getOrganiser();

        if (!is_null($cdbOrganizer)) {
            return $this->getCdbIdFromEmbeddedLocationOrOrganizer(
                $cdbOrganizer,
                $this->organizerExternalIdMappingService
            );
        } else {
            return null;
        }
    }

    /**
     * @param \CultureFeed_Cdb_Data_Location|\CultureFeed_Cdb_Data_Organiser $embeddedCdb
     * @param MappingServiceInterface $externalIdMappingService
     * @return null|string
     */
    private function getCdbIdFromEmbeddedLocationOrOrganizer(
        $embeddedCdb,
        MappingServiceInterface $externalIdMappingService
    ) {
        if (!is_null($embeddedCdb->getCdbid())) {
            return $embeddedCdb->getCdbid();
        }

        if (!is_null($embeddedCdb->getExternalId())) {
            return $externalIdMappingService->getCdbId(
                $embeddedCdb->getExternalId()
            );
        }

        if (!is_null($embeddedCdb->getActor()) && !is_null($embeddedCdb->getActor()->getCdbId())) {
            return $embeddedCdb->getActor()->getCdbId();
        }

        if (!is_null($embeddedCdb->getActor()) && !is_null($embeddedCdb->getActor()->getExternalId())) {
            return $externalIdMappingService->getCdbId(
                $embeddedCdb->getActor()->getExternalId()
            );
        }

        return null;
    }
}
