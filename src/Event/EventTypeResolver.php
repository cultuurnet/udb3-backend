<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Offer\TypeResolverInterface;
use Exception;

final class EventTypeResolver implements TypeResolverInterface
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
            throw new Exception('Unknown event type id: ' . $typeId);
        }
        return $this->types[$typeId];
    }

    public static function isOnlyAvailableUntilStartDate(Category $eventType): bool
    {
        return in_array(
            $eventType->getId()->toString(),
            [
                '0.3.1.0.0',
                '0.57.0.0.0',
            ]
        );
    }
}
