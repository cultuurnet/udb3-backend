<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;

abstract class OfferFacilityResolver implements OfferFacilityResolverInterface
{
    /**
     * @var Category[]
     */
    private array $facilities;

    public function __construct()
    {
        $this->facilities = $this->getFacilities();
    }

    public function byId(string $facilityId): Category
    {
        if (!array_key_exists($facilityId, $this->facilities)) {
            throw new \Exception("Unknown facility id '{$facilityId}'");
        }

        return $this->facilities[$facilityId];
    }

    /**
     * @return Category[]
     */
    abstract protected function getFacilities(): array;
}
