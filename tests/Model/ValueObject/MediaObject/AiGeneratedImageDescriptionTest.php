<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\MediaObject;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use PHPUnit\Framework\TestCase;

final class AiGeneratedImageDescriptionTest extends TestCase
{
    /**
     * @test
     * @dataProvider descriptionProvider
     */
    public function it_applies_the_ai_generated_suffix(
        string $description,
        string $language,
        ?bool $isAiGenerated,
        string $expected
    ): void {
        $this->assertEquals(
            $expected,
            AiGeneratedImageDescription::apply($description, new Language($language), $isAiGenerated)
        );
    }

    public function descriptionProvider(): array
    {
        return [
            'dutch suffix is added' => ['logo', 'nl', true, 'logo (Gegenereerd met AI)'],
            'french suffix is added' => ['logo', 'fr', true, 'logo (Généré par l\'IA)'],
            'german suffix is added' => ['logo', 'de', true, 'logo (Mit KI generiert)'],
            'english suffix is added' => ['logo', 'en', true, 'logo (Generated with AI)'],
            'suffix at the end is not duplicated' => [
                'logo (Gegenereerd met AI)',
                'nl',
                true,
                'logo (Gegenereerd met AI)',
            ],
            'suffix in the middle is not duplicated' => [
                'logo (Gegenereerd met AI) van publiq',
                'nl',
                true,
                'logo (Gegenereerd met AI) van publiq',
            ],
            'suffix of another language is replaced' => [
                'logo (Generated with AI)',
                'nl',
                true,
                'logo (Gegenereerd met AI)',
            ],
            'description with only the suffix of another language is replaced' => [
                '(Generated with AI)',
                'nl',
                true,
                '(Gegenereerd met AI)',
            ],
            'unknown language is not changed' => ['logo', 'es', true, 'logo'],
            'suffix at the end is removed' => ['logo (Gegenereerd met AI)', 'nl', false, 'logo'],
            'suffix in the middle is removed' => [
                'logo (Gegenereerd met AI) van publiq',
                'nl',
                false,
                'logo van publiq',
            ],
            'other whitespace is not changed when suffix is removed' => [
                'logo  van  publiq (Gegenereerd met AI)',
                'nl',
                false,
                'logo  van  publiq',
            ],
            'suffix at the start is removed' => ['(Gegenereerd met AI) logo', 'nl', false, 'logo'],
            'suffix of another language is removed' => ['logo (Generated with AI)', 'nl', false, 'logo'],
            'description without suffix is not changed when not ai generated' => ['logo', 'nl', false, 'logo'],
            'description with only the suffix is not emptied' => [
                '(Gegenereerd met AI)',
                'nl',
                false,
                '(Gegenereerd met AI)',
            ],
            'description is not changed when ai generated is unknown' => [
                'logo (Gegenereerd met AI)',
                'nl',
                null,
                'logo (Gegenereerd met AI)',
            ],
        ];
    }
}
