<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use PHPUnit\Framework\TestCase;

final class ExtractOfferNameTest extends TestCase
{
    /**
     * @dataProvider offerDataProvider
     */
    public function testExtractOfferName(array $offer, string $expectedResult): void
    {
        $result = ExtractOfferName::extract($offer);
        $this->assertEquals($expectedResult, $result);
    }

    public function offerDataProvider(): array
    {
        return [
            'nl key is present' => [
                ['name' => ['nl' => 'Dutch Offer']],
                'Dutch Offer',
            ],
            'mainLanguage key is present' => [
                ['name' => ['en' => 'English Offer', 'mainLanguage' => 'en']],
                'English Offer',
            ],
            'fallback to any available key' => [
                ['name' => ['fr' => 'French Offer', 'es' => 'Spanish Offer']],
                'French Offer',
            ],
            'empty array' => [
                [],
                '',
            ],
            'unexpected array structure' => [
                ['invalidKey' => 'Invalid Value'],
                '',
            ],
            'name is a string' => [
                ['name' => 'My name'],
                'My name',
            ],
        ];
    }
}
