<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Offer\TypeResolverInterface;
use Exception;

final class PlaceTypeResolver implements TypeResolverInterface
{
    /**
     * @param Category[] $types
     */
    public function __construct(readonly array $types)
    {
    }

    public function byId(string $typeId): Category
    {
        if (!array_key_exists($typeId, $this->types)) {
            throw new Exception('Unknown place type id: ' . $typeId);
        }
        return $this->types[$typeId];
    }
}
