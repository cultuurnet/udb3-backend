<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Taxonomy\Category;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class CategoryNormalizer implements NormalizerInterface
{
    /**
     * @param Category $object
     */
    public function normalize($object, $format = null, array $context = []): array
    {
        return [
            'id' => $object->getId()->toString(),
            'label' => $object->getLabel() ? $object->getLabel()->toString() : null,
            'domain' => $object->getDomain() ? $object->getDomain()->toString() : null,
        ];
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data === Category::class;
    }
}
