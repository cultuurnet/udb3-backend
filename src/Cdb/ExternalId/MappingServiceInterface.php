<?php

namespace CultuurNet\UDB3\Cdb\ExternalId;

interface MappingServiceInterface
{
    /**
     * @param string $externalId
     *
     * @return string|null
     *   Cdbid for the given external id, or null if none found.
     */
    public function getCdbId($externalId);
}
