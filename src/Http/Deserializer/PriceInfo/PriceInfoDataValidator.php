<?php

namespace CultuurNet\UDB3\Http\Deserializer\PriceInfo;

use CultuurNet\UDB3\Deserializer\DataValidationException;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Http\Deserializer\DataValidator\DataValidatorInterface;

class PriceInfoDataValidator implements DataValidatorInterface
{
    /**
     * @var Language|null
     */
    private $mainLanguage = null;

    /**
     * @param Language $language
     * @return PriceInfoDataValidator
     */
    public function forMainLanguage(Language $language)
    {
        $c = clone $this;
        $c->mainLanguage = $language;
        return $c;
    }

    /**
     * @inheritdoc
     */
    public function validate(array $data)
    {
        $messages = [];
        $basePrices = 0;

        foreach ($data as $key => $itemData) {
            $languageCodes = [];

            if (!isset($itemData['category'])) {
                $messages["[{$key}].category"] = 'Required but not found.';
            }

            if (!isset($itemData['name'])) {
                if (isset($itemData['category']) && $itemData['category'] !== 'base') {
                    $messages["[{$key}].name"] = 'Required but not found.';
                }
            } elseif (!is_array($itemData['name'])) {
                $messages["[{$key}].name"] = 'Name must be an associative array with language keys and translations.';
            } else {
                foreach ($itemData['name'] as $languageCode => $name) {
                    try {
                        new Language($languageCode);
                        $languageCodes[] = $languageCode;
                    } catch (\Exception $e) {
                        $messages["[{$key}].name.{$languageCode}"] = 'Invalid language code.';
                    }

                    if (!is_string($name)) {
                        $messages["[{$key}].name.{$languageCode}"] = 'Name translation must be a string.';
                    }
                }

                $mainLanguageCode = $this->mainLanguage ? $this->mainLanguage->getCode() : null;
                if ($mainLanguageCode && !in_array($mainLanguageCode, $languageCodes)) {
                    $messages["[{$key}].name"] = "Missing translation for mainLanguage '{$mainLanguageCode}'.";
                }
            }

            if (!isset($itemData['price'])) {
                $messages["[{$key}].price"] = 'Required but not found.';
            } elseif (!is_numeric($itemData['price'])) {
                $messages["[{$key}].price"] = 'Price must have a numeric value.';
            }

            if (isset($itemData['category']) && $itemData['category'] === 'base') {
                $basePrices++;

                if ($basePrices > 1) {
                    $messages["[{$key}].category"] =
                        "Exactly one entry with category 'base' allowed but found a duplicate.";
                }
            }
        }

        if ($basePrices < 1) {
            $messages['[].category'] = "Exactly one entry with category 'base' required but none found.";
        }

        if (count($messages) > 0) {
            $exception = new DataValidationException();
            $exception->setValidationMessages($messages);
            throw $exception;
        }
    }
}
