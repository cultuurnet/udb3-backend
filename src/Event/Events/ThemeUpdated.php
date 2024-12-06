<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Model\Serializer\ValueObject\Taxonomy\Category\CategoryDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Taxonomy\Category\CategoryNormalizer;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Offer\Events\AbstractEvent;

final class ThemeUpdated extends AbstractEvent
{
    protected Category $theme;

    final public function __construct(string $itemId, Category $theme)
    {
        parent::__construct($itemId);
        $this->theme = $theme;
    }

    public function getTheme(): Category
    {
        return $this->theme;
    }

    public function serialize(): array
    {
        return parent::serialize() + [
                'theme' => (new CategoryNormalizer())->normalize($this->theme),
            ];
    }

    /**
     * @inheritdoc
     */
    public static function deserialize(array $data)
    {
        return new static(
            $data['item_id'],
            (new CategoryDenormalizer(CategoryDomain::theme()))->denormalize($data['theme'], Category::class)
        );
    }
}
