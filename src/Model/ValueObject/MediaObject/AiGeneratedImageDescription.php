<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\MediaObject;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language;

final class AiGeneratedImageDescription
{
    private const SUFFIXES = [
        'nl' => '(Gegenereerd met AI)',
        'fr' => '(Généré par l\'IA)',
        'de' => '(Mit KI generiert)',
        'en' => '(Generated with AI)',
    ];

    /**
     * Adds the AI generated suffix in the language of the image when isAiGenerated is true,
     * and removes it again when isAiGenerated is false.
     * When isAiGenerated is null the description is returned unchanged.
     */
    public static function apply(string $description, Language $language, ?bool $isAiGenerated): string
    {
        if ($isAiGenerated === null) {
            return $description;
        }

        if ($isAiGenerated) {
            return self::addSuffix($description, $language);
        }

        return self::removeSuffixes($description);
    }

    private static function addSuffix(string $description, Language $language): string
    {
        $suffix = self::SUFFIXES[$language->getCode()] ?? null;

        if ($suffix === null || str_contains($description, $suffix)) {
            return $description;
        }

        return $description . ' ' . $suffix;
    }

    private static function removeSuffixes(string $description): string
    {
        // Remove each suffix together with the whitespace in front of it, so other whitespace is left alone.
        $pattern = '/\s*(?:' . implode('|', array_map(fn (string $suffix) => preg_quote($suffix, '/'), self::SUFFIXES)) . ')/u';
        $withoutSuffixes = preg_replace($pattern, '', $description, -1, $count);

        if ($withoutSuffixes === null || $count === 0) {
            return $description;
        }

        $withoutSuffixes = trim($withoutSuffixes);

        // A description that only consists of the suffix can not become empty.
        if ($withoutSuffixes === '') {
            return $description;
        }

        return $withoutSuffixes;
    }
}
