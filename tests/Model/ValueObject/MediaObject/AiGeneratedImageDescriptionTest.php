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
            'suffix in the middle is left alone' => [
                'logo (Gegenereerd met AI) van publiq',
                'nl',
                'logo (Gegenereerd met AI) van publiq (Gegenereerd met AI)',
            ],
            'unknown language is not changed' => ['logo', 'es', 'logo'],
        ];
    }
}
