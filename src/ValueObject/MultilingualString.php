<?php

namespace CultuurNet\UDB3\ValueObject;

use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Model\ValueObject\Translation\TranslatedValueObject;
use ValueObjects\StringLiteral\StringLiteral;

class MultilingualString
{
    /**
     * @var Language
     */
    private $originalLanguage;

    /**
     * @var StringLiteral
     */
    private $originalString;

    /**
     * @var StringLiteral[]
     *   Associative array with languages as keys and translations as values.
     */
    private $translations;

    public function __construct(Language $originalLanguage, StringLiteral $originalString)
    {
        $this->originalLanguage = $originalLanguage;
        $this->originalString = $originalString;
        $this->translations = [];
    }

    /**
     * @return Language
     */
    public function getOriginalLanguage()
    {
        return $this->originalLanguage;
    }

    /**
     * @return StringLiteral
     */
    public function getOriginalString()
    {
        return $this->originalString;
    }

    /**
     * @param Language $language
     * @param StringLiteral $translation
     * @return MultilingualString
     */
    public function withTranslation(Language $language, StringLiteral $translation)
    {
        if ($language->getCode() == $this->originalLanguage->getCode()) {
            throw new \InvalidArgumentException('Can not translate to original language.');
        }

        $c = clone $this;
        $c->translations[$language->getCode()] = $translation;
        return $c;
    }

    /**
     * @return StringLiteral[]
     *   Associative array with languages as keys and translations as values.
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * @return StringLiteral[]
     *   Associative array with languages as keys and translations as values.
     */
    public function getTranslationsIncludingOriginal()
    {
        return array_merge(
            [$this->originalLanguage->getCode() => $this->originalString],
            $this->translations
        );
    }

    /**
     * @param Language $preferredLanguage
     * @param Language[] ...$fallbackLanguages
     *   One or more accept languages.
     * @return StringLiteral|null
     */
    public function getStringForLanguage(Language $preferredLanguage, Language ...$fallbackLanguages)
    {
        $languages = $fallbackLanguages;
        array_unshift($languages, $preferredLanguage);

        $translations = $this->getTranslationsIncludingOriginal();

        foreach ($languages as $language) {
            if (isset($translations[$language->getCode()])) {
                return $translations[$language->getCode()];
            }
        }

        return null;
    }

    /**
     * @return array
     */
    public function serialize()
    {
        $serialized = [];

        foreach ($this->getTranslationsIncludingOriginal() as $language => $translation) {
            $serialized[$language] = $translation->toNative();
        }

        return $serialized;
    }

    /**
     * @param array $data
     * @param string|null $originalLanguage
     * @return MultilingualString
     */
    public static function deserialize(array $data, $originalLanguage = null)
    {
        $languages = array_keys($data);

        if (!$originalLanguage || !isset($data[$originalLanguage])) {
            $originalLanguage = reset($languages);
        }

        $string = new MultilingualString(new Language($originalLanguage), new StringLiteral($data[$originalLanguage]));
        foreach ($data as $language => $translation) {
            if ($language === $originalLanguage) {
                continue;
            }

            $string = $string->withTranslation(new Language($language), new StringLiteral($translation));
        }

        return $string;
    }

    /**
     * @param TranslatedValueObject $udb3Model
     * @return MultilingualString
     */
    public static function fromUdb3ModelTranslatedValueObject(TranslatedValueObject $udb3Model)
    {
        $originalLanguage = $udb3Model->getOriginalLanguage();
        $originalValue = $udb3Model->getTranslation($originalLanguage);

        if (!method_exists($originalValue, 'toString')) {
            throw new \InvalidArgumentException(
                'Cannot create MultilingualString from TranslatedValueObject that cannot be casted to string.'
            );
        }

        $string = new MultilingualString(
            Language::fromUdb3ModelLanguage($originalLanguage),
            new StringLiteral($originalValue->toString())
        );

        foreach ($udb3Model->getLanguagesWithoutOriginal() as $language) {
            $translation = $udb3Model->getTranslation($language);

            $string = $string->withTranslation(
                new Language($language->toString()),
                new StringLiteral($translation->toString())
            );
        }

        return $string;
    }
}
