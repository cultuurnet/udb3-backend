<?php

namespace CultuurNet\UDB3\Model\ValueObject\Translation;

abstract class TranslatedValueObject
{
    /**
     * @var Language
     */
    private $originalLanguage;

    /**
     * @var array
     */
    private $translations;

    /**
     * @param Language $originalLanguage
     * @param mixed $originalValueObject
     */
    public function __construct(Language $originalLanguage, $originalValueObject)
    {
        $this->guardValueObjectClassName($originalValueObject);

        $this->originalLanguage = $originalLanguage;
        $this->translations[$originalLanguage->getCode()] = $originalValueObject;
    }

    /**
     * @todo Use generics instead, if/when ever available in PHP.
     * @return string
     */
    abstract protected function getValueObjectClassName();

    /**
     * @param Language $language
     * @param mixed $translation
     * @return static
     */
    public function withTranslation(Language $language, $translation)
    {
        $this->guardValueObjectClassName($translation);

        $c = clone $this;
        $c->translations[$language->getCode()] = $translation;
        return $c;
    }

    /**
     * @param Language $language
     * @return static
     */
    public function withoutTranslation(Language $language)
    {
        if ($language->sameAs($this->originalLanguage)) {
            throw new \InvalidArgumentException('Can not remove translation of the original language.');
        }

        $c = clone $this;
        unset($c->translations[$language->getCode()]);
        return $c;
    }

    /**
     * @param Language $language
     * @return mixed
     * @throws \OutOfBoundsException
     */
    public function getTranslation(Language $language)
    {
        $languageCode = $language->getCode();

        if (!isset($this->translations[$languageCode])) {
            throw new \OutOfBoundsException("No translation found for language {$languageCode}");
        }

        return $this->translations[$languageCode];
    }

    /**
     * @return Language
     */
    public function getOriginalLanguage()
    {
        return $this->originalLanguage;
    }

    /**
     * @return Languages
     */
    public function getLanguages()
    {
        $languageKeys = array_keys($this->translations);

        $languageObjects = array_map(
            function ($languageCode) {
                return new Language($languageCode);
            },
            $languageKeys
        );

        return new Languages(...$languageObjects);
    }

    /**
     * @return Languages
     */
    public function getLanguagesWithoutOriginal()
    {
        return $this->getLanguages()->filter(
            function (Language $language) {
                return !$language->sameAs($this->originalLanguage);
            }
        );
    }

    /**
     * @param mixed $valueObject
     */
    private function guardValueObjectClassName($valueObject)
    {
        $className = $this->getValueObjectClassName();
        if (!($valueObject instanceof $className)) {
            $actualClassName = is_scalar($valueObject) ? gettype($valueObject) : get_class($valueObject);
            throw new \InvalidArgumentException("The given object is a {$actualClassName}, expected {$className}.");
        }
    }
}
