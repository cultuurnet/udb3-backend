<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Offer\OfferFacilityResolverInterface;

final class PlaceFacilityResolver implements OfferFacilityResolverInterface
{
    /**
     * @param Category[] $facilities
     */
    public function __construct(readonly array $facilities)
    {
    }

    public function byId(string $facilityId): Category
    {
        if (!array_key_exists($facilityId, $this->facilities)) {
            throw new \Exception("Unknown facility id '{$facilityId}'");
        }

        return $this->facilities[$facilityId];
    }
}
