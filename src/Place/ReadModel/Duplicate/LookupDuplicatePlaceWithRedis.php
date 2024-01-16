<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\Duplicate;

use CultuurNet\UDB3\Model\Place\Place;
use Doctrine\Common\Cache\Cache;
use GuzzleHttp\Psr7\Uri;

class LookupDuplicatePlaceWithRedis implements LookupDuplicatePlace
{
    private Uri $baseUri;
    private Cache $cache;
    private UniqueAddressIdentifierFactory $addressIdentifierFactory;
    private string $currentUserId;

    public function __construct(
        Cache $cache,
        UniqueAddressIdentifierFactory $addressIdentifierFactory,
        Uri $baseUri,
        string $currentUserId
    ) {
        $this->cache = $cache;
        $this->addressIdentifierFactory = $addressIdentifierFactory;
        $this->currentUserId = $currentUserId;
        $this->baseUri = $baseUri;
    }

    public function getDuplicatePlaceUri(Place $place): ?string
    {
        $duplicatePlaceId = $this->cache->fetch($this->addressIdentifierFactory->hash(
            $place->getTitle()->getTranslation($place->getMainLanguage())->toString(),
            $place->getAddress()->getTranslation($place->getMainLanguage()),
            $this->currentUserId
        ));

        if ($duplicatePlaceId === false) {
            return null;
        }

        // We have a place id, but to keep it consistent with sapi3, let us return a URI
        return $this->baseUri->__toString() . $duplicatePlaceId;
    }
}
