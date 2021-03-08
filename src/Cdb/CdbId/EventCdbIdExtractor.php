<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Cdb\CdbId;

use CultureFeed_Cdb_Item_Actor;
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
     * @return null|string
     */
    private function getCdbIdFromEmbeddedLocationOrOrganizer(
        $embeddedCdb,
        MappingServiceInterface $externalIdMappingService
    ) {
        if (!is_null($embeddedCdb->getCdbid())) {
            return $embeddedCdb->getCdbid();
        }

        /** @var string|null $externalId */
        $externalId = $embeddedCdb->getExternalId();
        if (!is_null($externalId)) {
            return $externalIdMappingService->getCdbId(
                $embeddedCdb->getExternalId()
            );
        }

        /** @var CultureFeed_Cdb_Item_Actor|null $actor */
        $actor = $embeddedCdb->getActor();
        /** @var string|null $actorId */
        $actorId = $actor ?? $actor->getCdbId();
        if ($actorId) {
            return $embeddedCdb->getActor()->getCdbId();
        }

        /** @var string|null $actorId */
        $actorId = $actor ?? $actor->getExternalId();
        if ($actorId) {
            return $externalIdMappingService->getCdbId(
                $embeddedCdb->getActor()->getExternalId()
            );
        }

        return null;
    }
}
