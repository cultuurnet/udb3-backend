<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Events;

use CultuurNet\UDB3\Model\Serializer\ValueObject\Taxonomy\Category\CategoryDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Taxonomy\Category\CategoryNormalizer;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;

abstract class AbstractFacilitiesUpdated extends AbstractEvent
{
    /**
     * @var Category[]
     */
    protected array $facilities;

    final public function __construct(string $id, array $facilities)
    {
        parent::__construct($id);
        $this->facilities = $facilities;
    }

    public function getFacilities(): array
    {
        return $this->facilities;
    }

    public static function deserialize(array $data): AbstractFacilitiesUpdated
    {
        $facilities = [];
        foreach ($data['facilities'] as $facility) {
            $facilities[] = (new CategoryDenormalizer(CategoryDomain::facility()))->denormalize($facility, Category::class);
        }

        return new static($data['item_id'], $facilities);
    }

    public function serialize(): array
    {
        $facilities = [];
        foreach ($this->facilities as $facility) {
            $facilities[] = (new CategoryNormalizer())->normalize($facility);
        }

        return parent::serialize() + [
            'facilities' => $facilities,
        ];
    }
}
