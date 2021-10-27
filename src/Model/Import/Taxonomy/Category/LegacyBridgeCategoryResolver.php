<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\Taxonomy\Category;

use CultuurNet\UDB3\Category as LegacyCategory;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Offer\OfferFacilityResolverInterface;
use CultuurNet\UDB3\Offer\ThemeResolverInterface;
use CultuurNet\UDB3\Offer\TypeResolverInterface;
use ValueObjects\StringLiteral\StringLiteral;

class LegacyBridgeCategoryResolver implements CategoryResolverInterface
{
    /**
     * @var TypeResolverInterface
     */
    private $typeResolver;

    /**
     * @var ThemeResolverInterface|null
     */
    private $themeResolver;

    /**
     * @var OfferFacilityResolverInterface
     */
    private $facilityResolver;


    public function __construct(
        TypeResolverInterface $typeResolver,
        OfferFacilityResolverInterface $facilityResolver,
        ?ThemeResolverInterface $themeResolver = null
    ) {
        $this->typeResolver = $typeResolver;
        $this->themeResolver = $themeResolver;
        $this->facilityResolver = $facilityResolver;
    }

    public function byId(CategoryID $categoryID): ?Category
    {
        $eventtype = $this->byIdInDomain($categoryID, new CategoryDomain('eventtype'));
        if ($eventtype) {
            return $eventtype;
        }

        $theme = $this->byIdInDomain($categoryID, new CategoryDomain('theme'));
        if ($theme) {
            return $theme;
        }

        $facility = $this->byIdInDomain($categoryID, new CategoryDomain('facility'));
        if ($facility) {
            return $theme;
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

        /** @var TypeResolverInterface|ThemeResolverInterface|OfferFacilityResolverInterface|null $resolver */
        $resolver = $resolverMap[$domain->toString()];
        if ($resolver === null) {
            return null;
        }

        try {
            /** @var LegacyCategory $legacyCategory */
            $legacyCategory = $resolver->byId(new StringLiteral($categoryID->toString()));
            return $this->convertLegacyCategory($legacyCategory);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function convertLegacyCategory(LegacyCategory $legacyCategory): Category
    {
        return new Category(
            new CategoryID($legacyCategory->getId()),
            new CategoryLabel($legacyCategory->getLabel()),
            new CategoryDomain($legacyCategory->getDomain())
        );
    }
}
