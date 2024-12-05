<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Taxonomy\Category;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Categories;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use Symfony\Component\Serializer\Exception\UnsupportedException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class CategoriesDenormalizer implements DenormalizerInterface
{
    /**
     * @inheritdoc
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        if (!$this->supportsDenormalization($data, $class, $format)) {
            throw new UnsupportedException("CategoriesDenormalizer does not support {$class}.");
        }

        if (!is_array($data)) {
            throw new UnsupportedException('Categories data should be an array.');
        }

        $categories = array_map(
            fn (array $categoryData): Category => $this->denormalizeCategory($categoryData),
            $data
        );
        return new Categories(...$categories);
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === Categories::class;
    }

    private function denormalizeCategory(array $categoryData): Category
    {
        return (new CategoryDenormalizer(null))->denormalize($categoryData, Category::class);
    }
}
