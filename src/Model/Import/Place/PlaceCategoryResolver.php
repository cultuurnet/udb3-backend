<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\Place;

use CultuurNet\UDB3\Model\Import\Taxonomy\Category\CategoryResolverInterface;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Offer\OfferFacilityResolverInterface;
use CultuurNet\UDB3\Offer\TypeResolverInterface;

class PlaceCategoryResolver implements CategoryResolverInterface
{
    public function __construct(
        readonly TypeResolverInterface $typeResolver,
        readonly OfferFacilityResolverInterface $facilityResolver,
    ) {
    }

    public function byId(CategoryID $categoryID): ?Category
    {
        $eventtype = $this->byIdInDomain($categoryID, CategoryDomain::eventType());
        if ($eventtype) {
            return $eventtype;
        }

        $facility = $this->byIdInDomain($categoryID, CategoryDomain::facility());
        if ($facility) {
            return $facility;
        }

        return null;
    }

    public function byIdInDomain(CategoryID $categoryID, CategoryDomain $domain): ?Category
    {
        $resolverMap = [
            'eventtype' => $this->typeResolver,
            'facility' => $this->facilityResolver,
        ];

        if (!isset($resolverMap[$domain->toString()])) {
            return null;
        }

        /** @var TypeResolverInterface|OfferFacilityResolverInterface|null $resolver */
        $resolver = $resolverMap[$domain->toString()];
        if ($resolver === null) {
            return null;
        }

        try {
            return $resolver->byId($categoryID->toString());
        } catch (\Exception $e) {
            return null;
        }
    }
}
