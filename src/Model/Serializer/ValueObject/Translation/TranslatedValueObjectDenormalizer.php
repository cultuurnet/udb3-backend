<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Translation;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Translation\TranslatedValueObject;
use InvalidArgumentException;
use Symfony\Component\Serializer\Exception\UnsupportedException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

abstract class TranslatedValueObjectDenormalizer implements DenormalizerInterface
{
    /**
     * @inheritdoc
     */public function denormalize($data, $class, $format = null, array $context = [])
    {
        if (!$this->supportsDenormalization($data, $class, $format)) {
            $selfClass = get_class($this);
            $selfClass = explode('\\', $selfClass);
            $selfClass = array_pop($selfClass);
            throw new UnsupportedException("{$selfClass} does not support {$class}.");
        }

        if (!is_array($data) || empty($data)) {
            throw new UnsupportedException(
                'Translated data should be an associative array with at least one value.'
            );
        }

        // Skip unsupported language codes to avoid any extra properties that are passed but not supported from
        // resulting in an error response.
        $languageKeys = array_keys($data);
        foreach ($languageKeys as $languageKey) {
            try {
                new Language($languageKey);
            } catch (InvalidArgumentException $e) {
                unset($data[$languageKey]);
            }
        }

        if (isset($context['originalLanguage'], $data[$context['originalLanguage']])) {
            $originalLanguageKey = $context['originalLanguage'];
        } else {
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
     * @return TranslatedValueObject
     */
    abstract protected function createTranslatedValueObject(Language $originalLanguage, object $originalValue);

    /**
     * @param string|array $value
     * @return object
     */
    abstract protected function createValueObject($value);
}
