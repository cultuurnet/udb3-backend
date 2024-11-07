<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\ValueObject;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Translation\TranslatedValueObject;

/**
 * @deprecated
 *   Use concrete instances of CultuurNet\UDB3\Model\ValueObject\Translation\TranslatedValueObject instead where
 *   possible.
 */
class MultilingualString
{
    private Language $originalLanguage;

    private string $originalString;

    /**
     * @var string[]
     *   Associative array with languages as keys and translations as values.
     */
    private array $translations;

    public function __construct(Language $originalLanguage, string $originalString)
    {
        $this->originalLanguage = $originalLanguage;
        $this->originalString = $originalString;
        $this->translations = [];
    }

    public function getOriginalLanguage(): Language
    {
        return $this->originalLanguage;
    }

    public function getOriginalString(): string
    {
        return $this->originalString;
    }

    public function withTranslation(Language $language, string $translation): self
    {
        if ($language->getCode() === $this->originalLanguage->getCode()) {
            throw new \InvalidArgumentException('Can not translate to original language.');
        }

        $c = clone $this;
        $c->translations[$language->getCode()] = $translation;
        return $c;
    }

    /**
     * @return string[]
     *   Associative array with languages as keys and translations as values.
     */
    public function getTranslations(): array
    {
        return $this->translations;
    }

    /**
     * @return string[]
     *   Associative array with languages as keys and translations as values.
     */
    public function getTranslationsIncludingOriginal(): array
    {
        return array_merge(
            [$this->originalLanguage->getCode() => $this->originalString],
            $this->translations
        );
    }

    /**
     * @param Language[] ...$fallbackLanguages
     *   One or more accept languages.
     */
    public function getStringForLanguage(Language $preferredLanguage, Language ...$fallbackLanguages): ?string
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

    public function serialize(): array
    {
        $serialized = [];

        foreach ($this->getTranslationsIncludingOriginal() as $language => $translation) {
            $serialized[$language] = $translation;
        }

        return $serialized;
    }

    public static function deserialize(array $data, string $originalLanguage = null): self
    {
        $languages = array_keys($data);

        if (!$originalLanguage || !isset($data[$originalLanguage])) {
            $originalLanguage = reset($languages);
        }

        $string = new MultilingualString(new Language($originalLanguage), $data[$originalLanguage]);
        foreach ($data as $language => $translation) {
            if ($language === $originalLanguage) {
                continue;
            }

            $string = $string->withTranslation(new Language($language), $translation);
        }

        return $string;
    }

    public static function fromUdb3ModelTranslatedValueObject(TranslatedValueObject $udb3Model): self
    {
        $originalLanguage = $udb3Model->getOriginalLanguage();
        $originalValue = $udb3Model->getTranslation($originalLanguage);

        if (!method_exists($originalValue, 'toString')) {
            throw new \InvalidArgumentException(
                'Cannot create MultilingualString from TranslatedValueObject that cannot be casted to string.'
            );
        }

        $string = new MultilingualString(
            $originalLanguage,
            $originalValue->toString()
        );

        foreach ($udb3Model->getLanguagesWithoutOriginal() as $language) {
            $translation = $udb3Model->getTranslation($language);

            $string = $string->withTranslation(
                new Language($language->toString()),
                $translation->toString()
            );
        }

        return $string;
    }
}
