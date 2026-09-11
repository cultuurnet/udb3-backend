<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Translation;

use stdClass;

/**
 * An older projection stores a name or an address untranslated, a newer one keys it by language,
 * and neither is guaranteed to carry a translation in the main language it is read with, so an
 * export falls back to any translation rather than leaving a column empty.
 *
 * @replay_i18n
 * @see https://jira.uitdatabank.be/browse/III-2201
 */
final class TranslatedProperty
{
    private const DEFAULT_LANGUAGE = 'nl';

    public static function mainLanguage(stdClass $document): string
    {
        $mainLanguage = $document->mainLanguage ?? null;
        return is_string($mainLanguage) ? $mainLanguage : self::DEFAULT_LANGUAGE;
    }

    public static function asString(mixed $property, string $mainLanguage): string
    {
        if (is_string($property)) {
            return $property;
        }

        if (!$property instanceof stdClass) {
            return '';
        }

        $translations = get_object_vars($property);
        $translation = $translations[$mainLanguage] ?? reset($translations);

        return is_string($translation) ? $translation : '';
    }

    public static function addressField(mixed $address, string $addressField, string $mainLanguage): string
    {
        if (!$address instanceof stdClass) {
            return '';
        }

        if (isset($address->{$addressField})) {
            return (string) $address->{$addressField};
        }

        if (isset($address->{$mainLanguage}->{$addressField})) {
            return (string) $address->{$mainLanguage}->{$addressField};
        }

        $translations = get_object_vars($address);
        $translation = reset($translations);

        if ($translation instanceof stdClass && isset($translation->{$addressField})) {
            return (string) $translation->{$addressField};
        }

        return '';
    }
}
