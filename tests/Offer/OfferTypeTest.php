<?php

namespace CultuurNet\UDB3\Offer;

use PHPUnit\Framework\TestCase;

class OfferTypeTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_an_event_option()
    {
        $relationsType = OfferType::EVENT();

        $this->assertEquals($relationsType, OfferType::EVENT);
    }

    /**
     * @test
     */
    public function it_has_a_place_option()
    {
        $relationsType = OfferType::PLACE();

        $this->assertEquals($relationsType, OfferType::PLACE);
    }

    /**
     * @test
     */
    public function it_has_only_an_event_and_place_option()
    {
        $options = OfferType::getConstants();

        $this->assertEquals(
            [
                OfferType::EVENT()->getName() => OfferType::EVENT,
                OfferType::PLACE()->getName() => OfferType::PLACE,
            ],
            $options
        );
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     *
     * @param string $enumValue
     * @param OfferType $expectedOfferType
     */
    public function it_can_be_created_from_a_value_with_incorrect_casing(
        $enumValue,
        OfferType $expectedOfferType
    ) {
        $actualOfferType = OfferType::fromCaseInsensitiveValue($enumValue);
        $this->assertTrue($expectedOfferType->sameValueAs($actualOfferType));
    }

    public function offerTypeDataProvider()
    {
        return [
            [
                'place',
                OfferType::PLACE(),
            ],
            [
                'eVeNt',
                OfferType::EVENT(),
            ],
            [
                'Place',
                OfferType::PLACE(),
            ],
            [
                'EVENT',
                OfferType::EVENT(),
            ],
        ];
    }
}
