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
    public function it_adds_the_ai_generated_suffix(
        string $description,
        string $language,
        string $expected
    ): void {
        $this->assertEquals(
            $expected,
            AiGeneratedImageDescription::addSuffix($description, new Language($language))
        );
    }

    public function descriptionProvider(): array
    {
        return [
            'dutch suffix is added' => ['logo', 'nl', 'logo (Gegenereerd met AI)'],
            'french suffix is added' => ['logo', 'fr', 'logo (Généré par l\'IA)'],
            'german suffix is added' => ['logo', 'de', 'logo (Mit KI generiert)'],
            'english suffix is added' => ['logo', 'en', 'logo (Generated with AI)'],
            'suffix at the end is not duplicated' => [
                'logo (Gegenereerd met AI)',
                'nl',
                'logo (Gegenereerd met AI)',
            ],
            'suffix in the middle is not duplicated' => [
                'logo (Gegenereerd met AI) van publiq',
                'nl',
                'logo (Gegenereerd met AI) van publiq',
            ],
            'unknown language is not changed' => ['logo', 'es', 'logo'],
        ];
    }

    /**
     * @test
     * @dataProvider removeSuffixProvider
     */
    public function it_removes_the_ai_generated_suffix(
        string $description,
        string $language,
        string $expected
    ): void {
        $this->assertEquals(
            $expected,
            AiGeneratedImageDescription::removeSuffix($description, new Language($language))
        );
    }

    public function removeSuffixProvider(): array
    {
        return [
            'dutch suffix is removed' => ['logo (Gegenereerd met AI)', 'nl', 'logo'],
            'french suffix is removed' => ['logo (Généré par l\'IA)', 'fr', 'logo'],
            'german suffix is removed' => ['logo (Mit KI generiert)', 'de', 'logo'],
            'english suffix is removed' => ['logo (Generated with AI)', 'en', 'logo'],
            'description without suffix is not changed' => ['logo', 'nl', 'logo'],
            'suffix in the middle is not removed' => [
                'logo (Gegenereerd met AI) van publiq',
                'nl',
                'logo (Gegenereerd met AI) van publiq',
            ],
            'suffix of another language is not removed' => [
                'logo (Generated with AI)',
                'nl',
                'logo (Generated with AI)',
            ],
            'unknown language is not changed' => ['logo (Generated with AI)', 'es', 'logo (Generated with AI)'],
        ];
    }
}
