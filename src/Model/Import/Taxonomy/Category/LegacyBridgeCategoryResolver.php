<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\Taxonomy\Category;

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

    public function byId(CategoryID $categoryID)
    {
        $category = null;

        try {
            $category = $this->typeResolver->byId(new StringLiteral($categoryID->toString()));
        } catch (\Exception $e) {
            // Do nothing.
        }

        try {
            $category = $this->themeResolver->byId(new StringLiteral($categoryID->toString()));
        } catch (\Exception $e) {
            // Do nothing.
        }

        try {
            $category = $this->facilityResolver->byId(new StringLiteral($categoryID->toString()));
        } catch (\Exception $e) {
            // Do nothing.
        }

        if (!$category) {
            return null;
        }

        return new Category(
            $categoryID,
            new CategoryLabel($category->getLabel()),
            new CategoryDomain($category->getDomain())
        );
    }
}
