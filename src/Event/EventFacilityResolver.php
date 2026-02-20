<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Categories;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Offer\OfferFacilityResolverInterface;
use Exception;

class EventFacilityResolver implements OfferFacilityResolverInterface
{
    public function __construct(readonly Categories $facilities)
    {
    }

    public function byId(string $facilityId): Category
    {
        $category = $this->facilities->getById(new CategoryID($facilityId));
        if ($category === null) {
            throw new Exception("Unknown facility id '{$facilityId}'");
        }

        return $category;
    }
}
