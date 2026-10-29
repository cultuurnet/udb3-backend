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
     * Adds the AI generated suffix in the language of the image.
     * The suffix is only added when projecting, the stored description is never changed.
     */
    public static function addSuffix(string $description, Language $language): string
    {
        $suffix = self::SUFFIXES[$language->getCode()] ?? null;

        if ($suffix === null || str_contains($description, $suffix)) {
            return $description;
        }

        return $description . ' ' . $suffix;
    }

    /**
     * Removes the AI generated suffix in the language of the image, but only when it is at the end of the description.
     */
    public static function removeSuffix(string $description, Language $language): string
    {
        $suffix = self::SUFFIXES[$language->getCode()] ?? null;

        if ($suffix === null || !str_ends_with($description, ' ' . $suffix)) {
            return $description;
        }

        return substr($description, 0, -strlen(' ' . $suffix));
    }
}
