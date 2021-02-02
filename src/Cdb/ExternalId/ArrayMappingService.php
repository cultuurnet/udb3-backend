<?php

namespace CultuurNet\UDB3\Cdb\ExternalId;

class ArrayMappingService implements MappingServiceInterface
{
    /**
     * @var array
     */
    private $externalIdMapping;

    /**
     * @param array $externalIdMapping
     *   Associative array of external ids and their corresponding cdbids.
     */
    public function __construct(array $externalIdMapping)
    {
        $this->externalIdMapping = $externalIdMapping;
    }

    /**
     * @param string $externalId
     * @return string|null
     */
    public function getCdbId($externalId)
    {
        if (isset($this->externalIdMapping[$externalId])) {
            return (string) $this->externalIdMapping[$externalId];
        } else {
            return null;
        }
    }
}
