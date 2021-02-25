<?php

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Translation;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Translation\TranslatedValueObject;
use Symfony\Component\Serializer\Exception\UnsupportedException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

abstract class TranslatedValueObjectDenormalizer implements DenormalizerInterface
{
    /**
     * @inheritdoc
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        if (!$this->supportsDenormalization($data, $class, $format)) {
            $selfClass = get_class($this);
            $selfClass = explode('\\', $selfClass);
            $selfClass = $selfClass[0];
            throw new UnsupportedException("{$selfClass} does not support {$class}.");
        }

        if (!is_array($data) || empty($data)) {
            throw new UnsupportedException(
                'Translated data should be an associative array with at least one value.'
            );
        }

        if (isset($context['originalLanguage'])) {
            $originalLanguageKey = $context['originalLanguage'];
        } else {
            $languageKeys = array_keys($data);
            $originalLanguageKey = $languageKeys[0];
        }

        $originalLanguage = new Language($originalLanguageKey);
        $originalValue = $this->createValueObject($data[$originalLanguageKey]);

        $translated = $this->createTranslatedValueObject($originalLanguage, $originalValue);

        foreach ($data as $languageKey => $valueTranslation) {
            if ($languageKey == $originalLanguageKey) {
                continue;
            }

            $translated = $translated->withTranslation(
                new Language($languageKey),
                $this->createValueObject($valueTranslation)
            );
        }

        return $translated;
    }

    /**
     * @param Language $originalLanguage
     * @param string $originalValue
     * @return TranslatedValueObject
     */
    abstract protected function createTranslatedValueObject(Language $originalLanguage, $originalValue);

    /**
     * @param mixed $value
     * @return object
     */
    abstract protected function createValueObject($value);
}
