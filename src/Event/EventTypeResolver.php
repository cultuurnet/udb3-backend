<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Categories;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Offer\TypeResolverInterface;
use Exception;

final class EventTypeResolver implements TypeResolverInterface
{
    public const CAMP_OR_VACATION_TERM_ID = '0.57.0.0.0';

    public function __construct(readonly Categories $types)
    {
    }

    public function byId(string $typeId): Category
    {
        $category = $this->types->getById(new CategoryID($typeId));
        if ($category === null) {
            throw new Exception('Unknown event type id: ' . $typeId);
        }

        return $category;
    }

    public static function isOnlyAvailableUntilStartDate(Category $eventType): bool
    {
        return in_array(
            $eventType->getId()->toString(),
            [
                '0.3.1.0.0',
                self::CAMP_OR_VACATION_TERM_ID,
            ]
        );
    }

    public static function isOvernightAllowed(string $eventTermId) : bool
    {
        return $eventTermId === self::CAMP_OR_VACATION_TERM_ID;
    }
}
