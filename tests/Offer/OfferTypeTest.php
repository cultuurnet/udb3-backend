<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use PHPUnit\Framework\TestCase;

class OfferTypeTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_an_event_option(): void
    {
        $relationsType = new OfferType('Event');

        $this->assertEquals($relationsType, OfferType::event());
    }

    /**
     * @test
     */
    public function it_has_a_place_option(): void
    {
        $relationsType = new OfferType('Place');

        $this->assertEquals($relationsType, OfferType::place());
    }

    /**
     * @test
     */
    public function it_has_only_an_event_and_place_option(): void
    {
        $options = OfferType::getAllowedValues();

        $this->assertEquals(
            [
                OfferType::event()->toString(),
                OfferType::place()->toString(),
            ],
            $options
        );
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_can_be_created_from_a_value_with_incorrect_casing(
        string $enumValue,
        OfferType $expectedOfferType
    ): void {
        $actualOfferType = OfferType::fromCaseInsensitiveValue($enumValue);
        $this->assertTrue($expectedOfferType->sameAs($actualOfferType));
    }

    public function offerTypeDataProvider(): array
    {
        return [
            [
                'place',
                OfferType::place(),
            ],
            [
                'eVeNt',
                OfferType::event(),
            ],
            [
                'Place',
                OfferType::place(),
            ],
            [
                'EVENT',
                OfferType::event(),
            ],
        ];
    }
}
