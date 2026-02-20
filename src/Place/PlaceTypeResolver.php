<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Categories;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Offer\TypeResolverInterface;
use Exception;

final class PlaceTypeResolver implements TypeResolverInterface
{
    public function __construct(readonly Categories $types)
    {
    }

    public function byId(string $typeId): Category
    {
        $category = $this->types->getById(new CategoryID($typeId));
        if ($category === null) {
            throw new Exception("Unknown place type id: '{$typeId}'");
        }

        return $category;
    }
}
