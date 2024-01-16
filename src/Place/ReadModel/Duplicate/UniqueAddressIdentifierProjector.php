<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\Duplicate;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use Doctrine\Common\Cache\Cache;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;

class UniqueAddressIdentifierProjector implements EventListener
{
    private const ONE_WEEK = 604800;
    private Cache $cache;
    private UniqueAddressIdentifierFactory $addressIdentifierFactory;
    private string $currentUserId;

    public function __construct(
        Cache $cache,
        UniqueAddressIdentifierFactory $addressIdentifierFactory,
        string $currentUserId
    ) {
        $this->cache = $cache;
        $this->addressIdentifierFactory = $addressIdentifierFactory;
        $this->currentUserId = $currentUserId;
    }

    public function handle(DomainMessage $domainMessage): void
    {
        $placeCreated = $domainMessage->getPayload();
        if (!$placeCreated instanceof PlaceCreated) {
            return;
        }

        $this->cache->save(
            $this->addressIdentifierFactory->hash(
                $placeCreated->getTitle()->toString(),
                new Address(
                    new Street($placeCreated->getAddress()->getStreetAddress()->toString()),
                    new PostalCode($placeCreated->getAddress()->getPostalCode()->toString()),
                    new Locality($placeCreated->getAddress()->getLocality()->toString()),
                    $placeCreated->getAddress()->getCountryCode(),
                ),
                $this->currentUserId
            ),
            $placeCreated->getPlaceId(),
            self::ONE_WEEK
        );
    }
}
