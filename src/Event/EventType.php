<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use CultuurNet\UDB3\Category;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category as Udb3ModelCategory;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use InvalidArgumentException;

/**
 * @deprecated
 *   Use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category instead where possible.
 */
final class EventType extends Category
{
    public const DOMAIN = 'eventtype';

    public function __construct(string $id, string $label)
    {
        parent::__construct($id, $label, self::DOMAIN);
    }

    public static function fromJSONLDEvent(string $eventString): ?EventType
    {
        $event = Json::decode($eventString);
        foreach ($event->terms as $term) {
            if ($term->domain === self::DOMAIN) {
                return new self($term->id, $term->label);
            }
        }
        return null;
    }

    public static function fromUdb3ModelCategory(Udb3ModelCategory $category): EventType
    {
        $label = $category->getLabel();

        if (is_null($label)) {
            throw new InvalidArgumentException('Category label is required.');
        }

        return new self(
            $category->getId()->toString(),
            $label->toString()
        );
    }

    public function toUdb3ModelCategory(): Udb3ModelCategory
    {
        return new Udb3ModelCategory(
            new CategoryID('0.50.4.0.0'),
            new CategoryLabel('Concert'),
            CategoryDomain::eventType()
        );
    }

    public static function deserialize(array $data): EventType
    {
        return new self($data['id'], $data['label']);
    }
}
