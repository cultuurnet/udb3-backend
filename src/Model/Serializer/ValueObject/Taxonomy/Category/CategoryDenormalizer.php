<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Taxonomy\Category;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use Symfony\Component\Serializer\Exception\UnsupportedException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class CategoryDenormalizer implements DenormalizerInterface
{
    private ?CategoryDomain $defaultDomain;

    public function __construct(?CategoryDomain $defaultDomain)
    {
        $this->defaultDomain = $defaultDomain;
    }

    public function denormalize($data, $class, $format = null, array $context = []): Category
    {
        if (!$this->supportsDenormalization($data, $class, $format)) {
            throw new UnsupportedException("CategoryDenormalizer does not support {$class}.");
        }

        if (!is_array($data)) {
            throw new UnsupportedException('Category data should be an array.');
        }

        return new Category(
            new CategoryID($data['id']),
            isset($data['label']) ? new CategoryLabel($data['label']) : null,
            $this->createCategoryDomain($data)
        );
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === Category::class;
    }

    private function createCategoryDomain(array $data): ?CategoryDomain
    {
        if ($this->defaultDomain) {
            return $this->defaultDomain;
        }

        return isset($data['domain']) ? new CategoryDomain($data['domain']) : null;
    }
}
