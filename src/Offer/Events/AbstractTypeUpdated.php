<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Events;

use CultuurNet\UDB3\Model\Serializer\ValueObject\Taxonomy\Category\CategoryDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Taxonomy\Category\CategoryNormalizer;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;

abstract class AbstractTypeUpdated extends AbstractEvent
{
    protected Category $type;

    final public function __construct(string $itemId, Category $type)
    {
        parent::__construct($itemId);
        $this->type = $type;
    }

    public function getType(): Category
    {
        return $this->type;
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'type' => (new CategoryNormalizer())->normalize($this->type),
        ];
    }

    public static function deserialize(array $data): AbstractTypeUpdated
    {
        return new static(
            $data['item_id'],
            (new CategoryDenormalizer(CategoryDomain::eventType()))->denormalize($data['type'], Category::class)
        );
    }
}
