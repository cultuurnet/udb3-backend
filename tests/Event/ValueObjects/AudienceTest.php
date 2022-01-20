<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ValueObjects;

use CultuurNet\UDB3\Model\ValueObject\Audience\AudienceType;
use PHPUnit\Framework\TestCase;

class AudienceTest extends TestCase
{
    /**
     * @var string
     */
    private $itemId;

    /**
     * @var AudienceType
     */
    private $audienceType;

    /**
     * @var Audience
     */
    private $audience;

    protected function setUp()
    {
        $this->itemId = '6eaaa9b6-d0d2-11e6-bf26-cec0c932ce01';

        $this->audienceType = AudienceType::education();

        $this->audience = new Audience(
            $this->audienceType
        );
    }

    /**
     * @test
     */
    public function if_stores_an_audience_type()
    {
        $this->assertEquals(
            $this->audienceType,
            $this->audience->getAudienceType()
        );
    }

    /**
     * @test
     */
    public function if_can_deserialize_from_an_array()
    {
        $this->assertEquals(
            $this->audience,
            Audience::deserialize(
                [
                    'audienceType' => $this->audienceType->toString(),
                ]
            )
        );
    }

    /**
     * @test
     */
    public function if_serialize_to_an_array()
    {
        $this->assertEquals(
            [
                'audienceType' => $this->audienceType->toString(),
            ],
            $this->audience->serialize()
        );
    }

    /**
     * @test
     * @dataProvider audienceDataProvider
     * @param bool $equal
     */
    public function it_can_check_for_equality(
        Audience $audience,
        Audience $otherAudience,
        $equal
    ) {
        $this->assertEquals(
            $equal,
            $audience->equals($otherAudience)
        );
    }

    /**
     * @return array
     */
    public function audienceDataProvider()
    {
        return [
            'equal audience' =>
                [
                    new Audience(AudienceType::education()),
                    new Audience(AudienceType::education()),
                    true,
                ],
            'different audience' =>
                [
                    new Audience(AudienceType::education()),
                    new Audience(AudienceType::everyone()),
                    false,
                ],
        ];
    }
}
