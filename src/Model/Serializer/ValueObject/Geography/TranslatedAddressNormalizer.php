<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Geography;

use CultuurNet\UDB3\Model\ValueObject\Geography\TranslatedAddress;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class TranslatedAddressNormalizer implements NormalizerInterface
{
    public function normalize($object, $format = null, array $context = [])
    {
        if (!$this->supportsNormalization($object)) {
            throw new InvalidArgumentException(sprintf('Invalid object type, expected %s, received %s.', TranslatedAddress::class, get_class($object)));
        }

        $output = [];

        /** @var Language $language */
        foreach ($object->getLanguages() as $language) {
            $translatedAddress = $object->getTranslation($language);

            $output[$language->getCode()] = [
                'street' => $translatedAddress->getStreet()->toString(),
                'postalCode' => $translatedAddress->getPostalCode()->toString(),
                'locality' => $translatedAddress->getLocality()->toString(),
                'countryCode' => $translatedAddress->getCountryCode()->toString(),
            ];
        }

        return $output;
    }

    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof TranslatedAddress;
    }
}
