<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Translation;

abstract class TranslatedValueObject
{
    private Language $originalLanguage;

    private array $translations;

    public function __construct(Language $originalLanguage, object $originalValueObject)
    {
        $this->guardValueObjectClassName($originalValueObject);

        $this->originalLanguage = $originalLanguage;
        $this->translations[$originalLanguage->getCode()] = $originalValueObject;
    }

    /**
     * @todo Use generics instead, if/when ever available in PHP.
     */
    abstract protected function getValueObjectClassName(): string;

    /**
     * @return static
     */
    public function withTranslation(Language $language, object $translation)
    {
        $this->guardValueObjectClassName($translation);

        $c = clone $this;
        $c->translations[$language->getCode()] = $translation;
        return $c;
    }

    /**
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
     * @throws \OutOfBoundsException
     */
    public function getTranslation(Language $language): object
    {
        $languageCode = $language->getCode();

        if (!isset($this->translations[$languageCode])) {
            throw new \OutOfBoundsException("No translation found for language {$languageCode}");
        }

        return $this->translations[$languageCode];
    }

    public function getOriginalLanguage(): Language
    {
        return $this->originalLanguage;
    }

    public function getLanguages(): Languages
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

    public function getLanguagesWithoutOriginal(): Languages
    {
        return $this->getLanguages()->filter(
            function (Language $language) {
                return !$language->sameAs($this->originalLanguage);
            }
        );
    }

    public function getOriginalValue(): object
    {
        return $this->getTranslation($this->getOriginalLanguage());
    }

    private function guardValueObjectClassName(object $valueObject): void
    {
        $className = $this->getValueObjectClassName();
        if (!($valueObject instanceof $className)) {
            $actualClassName = get_class($valueObject);
            throw new \InvalidArgumentException("The given object is a {$actualClassName}, expected {$className}.");
        }
    }
}
