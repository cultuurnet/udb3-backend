<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Taxonomy\Category;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Categories;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
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

        $categories = array_map([$this, 'denormalizeCategory'], $data);
        return new Categories(...$categories);
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === Categories::class;
    }

    /**
     * @todo Extract to a separate CategoryDenormalizer
     */
    private function denormalizeCategory(array $categoryData): Category
    {
        $id = new CategoryID($categoryData['id']);
        $label = isset($categoryData['label']) ? new CategoryLabel($categoryData['label']) : null;
        $domain = isset($categoryData['domain']) ? new CategoryDomain($categoryData['domain']) : null;
        return new Category($id, $label, $domain);
    }
}
