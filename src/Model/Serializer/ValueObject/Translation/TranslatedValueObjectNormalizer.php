<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Translation;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Translation\TranslatedValueObject;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

abstract class TranslatedValueObjectNormalizer implements NormalizerInterface
{
    /**
     * @param TranslatedValueObject $object
     */
    public function normalize($object, $format = null, array $context = []): array
    {
        $data = [];

        /** @var Language $language */
        foreach ($object->getLanguages() as $language) {
            $data[$language->toString()] = $object->getTranslation($language)->toString();
        }

        return $data;
    }
}
