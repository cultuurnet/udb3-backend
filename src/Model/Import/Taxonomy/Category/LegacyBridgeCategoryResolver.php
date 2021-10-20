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
     * @var ThemeResolverInterface
     */
    private $themeResolver;

    /**
     * @var OfferFacilityResolverInterface
     */
    private $facilityResolver;


    public function __construct(
        TypeResolverInterface $typeResolver,
        ThemeResolverInterface $themeResolver,
        OfferFacilityResolverInterface $facilityResolver
    ) {
        $this->typeResolver = $typeResolver;
        $this->themeResolver = $themeResolver;
        $this->facilityResolver = $facilityResolver;
    }

    public function byId(CategoryID $categoryID): ?Category
    {
        $legacyCategory = null;

        try {
            $legacyCategory = $this->typeResolver->byId(new StringLiteral($categoryID->toString()));
        } catch (\Exception $e) {
            // Do nothing.
        }

        try {
            $legacyCategory = $this->themeResolver->byId(new StringLiteral($categoryID->toString()));
        } catch (\Exception $e) {
            // Do nothing.
        }

        try {
            $legacyCategory = $this->facilityResolver->byId(new StringLiteral($categoryID->toString()));
        } catch (\Exception $e) {
            // Do nothing.
        }

        return $legacyCategory ? $this->convertLegacyCategory($legacyCategory) : null;
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
