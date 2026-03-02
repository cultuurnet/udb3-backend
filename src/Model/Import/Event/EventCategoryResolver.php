<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\Event;

use CultuurNet\UDB3\Model\Import\Taxonomy\Category\CategoryResolverInterface;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Offer\OfferFacilityResolverInterface;
use CultuurNet\UDB3\Offer\ThemeResolverInterface;
use CultuurNet\UDB3\Offer\TypeResolverInterface;

final class EventCategoryResolver implements CategoryResolverInterface
{
    public function __construct(
        private readonly TypeResolverInterface $typeResolver,
        private readonly OfferFacilityResolverInterface $facilityResolver,
        private readonly ThemeResolverInterface $themeResolver
    ) {
    }

    public function byId(CategoryID $categoryID): ?Category
    {
        $eventtype = $this->byIdInDomain($categoryID, CategoryDomain::eventType());
        if ($eventtype) {
            return $eventtype;
        }

        $theme = $this->byIdInDomain($categoryID, CategoryDomain::theme());
        if ($theme) {
            return $theme;
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
            'theme' => $this->themeResolver,
            'facility' => $this->facilityResolver,
        ];

        if (!isset($resolverMap[$domain->toString()])) {
            return null;
        }

        /** @var TypeResolverInterface|ThemeResolverInterface|OfferFacilityResolverInterface $resolver */
        $resolver = $resolverMap[$domain->toString()];

        try {
            return $resolver->byId($categoryID->toString());
        } catch (\Exception $e) {
            return null;
        }
    }
}
