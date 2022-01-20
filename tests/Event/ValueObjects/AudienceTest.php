<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ValueObjects;

use CultuurNet\UDB3\Model\ValueObject\Audience\AudienceType;
use PHPUnit\Framework\TestCase;

class AudienceTest extends TestCase
{
    private string $itemId;

    private AudienceType $audienceType;

    private Audience $audience;

    protected function setUp(): void
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
    public function if_stores_an_audience_type(): void
    {
        $this->assertEquals(
            $this->audienceType,
            $this->audience->getAudienceType()
        );
    }

    /**
     * @test
     */
    public function if_can_deserialize_from_an_array(): void
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
    public function if_serialize_to_an_array(): void
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
     */
    public function it_can_check_for_equality(
        Audience $audience,
        Audience $otherAudience,
        bool $equal
    ): void {
        $this->assertEquals(
            $equal,
            $audience->equals($otherAudience)
        );
    }

    public function audienceDataProvider(): array
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
