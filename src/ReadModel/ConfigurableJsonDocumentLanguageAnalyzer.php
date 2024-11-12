<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\ReadModel;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language;

class ConfigurableJsonDocumentLanguageAnalyzer implements JsonDocumentLanguageAnalyzerInterface
{
    /**
     * @var string[]
     */
    private array $translatableProperties;

    /**
     * @param string[] $translatableProperties
     *   List of translatable properties (on top level).
     */
    public function __construct(
        array $translatableProperties
    ) {
        $this->translatableProperties = $translatableProperties;
    }

    /**
     * @return Language[]
     */
    public function determineAvailableLanguages(JsonDocument $jsonDocument): array
    {
        $json = $jsonDocument->getBody();
        $languageStrings = [];

        foreach ($this->translatableProperties as $translatableProperty) {
            $languageStringsOnProperty = $this->getLanguageStrings($json, $translatableProperty);

            $languageStrings = array_merge(
                $languageStrings,
                $languageStringsOnProperty
            );
        }

        $languageStrings = array_values(array_unique($languageStrings));

        return $this->getLanguageStringsAsValueObjects($languageStrings);
    }

    /**
     * @return Language[]
     */
    public function determineCompletedLanguages(JsonDocument $jsonDocument): array
    {
        $json = $jsonDocument->getBody();
        $languageStrings = [];

        foreach ($this->translatableProperties as $translatableProperty) {
            $languageStringsOnProperty = $this->getLanguageStrings($json, $translatableProperty);

            if (empty($languageStringsOnProperty)) {
                // Property was not found, which means it's not set for the
                // original language either. Skip it, as it can't be translated
                // without an original value.
                continue;
            }

            if ($translatableProperty == $this->translatableProperties[0]) {
                $languageStrings = $languageStringsOnProperty;
            } else {
                $languageStrings = array_intersect($languageStrings, $languageStringsOnProperty);
            }
        }

        $languageStrings = array_values(array_unique($languageStrings));

        return $this->getLanguageStringsAsValueObjects($languageStrings);
    }

    /**
     * @return string[]
     */
    private function getLanguageStrings(\stdClass $json, string $propertyName): array
    {
        if (strpos($propertyName, '.') === false) {
            return $this->getLanguageStringsFromProperty($json, $propertyName);
        } else {
            return $this->getLanguageStringsFromNestedProperty($json, $propertyName);
        }
    }

    /**
     * @return string[]
     */
    private function getLanguageStringsFromProperty(\stdClass $json, string $propertyName): array
    {
        if (!isset($json->{$propertyName})) {
            return [];
        }

        if (!is_object($json->{$propertyName})) {
            return [];
        }

        return array_keys(
            get_object_vars($json->{$propertyName})
        );
    }

    /**
     * @return string[]
     */
    private function getLanguageStringsFromNestedProperty(\stdClass $json, string $propertyName): array
    {
        $nestedProperties = explode('.', $propertyName);
        $traversedProperties = [];
        $propertyReference = $json;

        $languages = [];

        while ($nestedPropertyName = array_shift($nestedProperties)) {
            if ($nestedPropertyName === '[]') {
                foreach ($propertyReference as $key => $arrayItem) {
                    $remainingPath = implode('.', $nestedProperties);

                    $recursiveLanguages = $this->getLanguageStringsFromNestedProperty(
                        $propertyReference[$key],
                        $remainingPath
                    );

                    $languages = array_merge($languages, $recursiveLanguages);
                }
                return $languages;
            }

            if (!isset($propertyReference->{$nestedPropertyName})) {
                // Is either optional or should be handled by a different rule.
                return [];
            }

            $propertyReference = $propertyReference->{$nestedPropertyName};
            $traversedProperties[] = $nestedPropertyName;
        }

        if (is_object($propertyReference)) {
            return array_keys(get_object_vars($propertyReference));
        } else {
            return [];
        }
    }

    /**
     * @param string[] $languageStrings
     * @return Language[]
     */
    private function getLanguageStringsAsValueObjects(array $languageStrings): array
    {
        return array_map(
            function ($languageString) {
                return new Language($languageString);
            },
            $languageStrings
        );
    }
}
