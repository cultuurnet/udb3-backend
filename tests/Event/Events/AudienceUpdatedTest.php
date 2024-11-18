<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Model\ValueObject\Audience\AudienceType;
use PHPUnit\Framework\TestCase;

class AudienceUpdatedTest extends TestCase
{
    private string $itemId;

    private AudienceType $audienceType;

    private AudienceUpdated $audienceUpdated;

    protected function setUp(): void
    {
        $this->itemId = '6eaaa9b6-d0d2-11e6-bf26-cec0c932ce01';

        $this->audienceType = AudienceType::members();

        $this->audienceUpdated = new AudienceUpdated(
            $this->itemId,
            $this->audienceType
        );
    }

    /**
     * @test
     */
    public function it_should_identify_the_updated_event_by_item_id(): void
    {
        $this->assertEquals('6eaaa9b6-d0d2-11e6-bf26-cec0c932ce01', $this->audienceUpdated->getItemId());
    }

    /**
     * @test
     */
    public function it_stores_an_audience(): void
    {
        $this->assertEquals(
            $this->audienceType,
            $this->audienceUpdated->getAudienceType()
        );
    }

    /**
     * @test
     */
    public function it_can_serialize_to_an_array(): void
    {
        $this->assertEquals(
            [
                'item_id' => $this->itemId,
                'audience' => ['audienceType' => 'members'],
            ],
            $this->audienceUpdated->serialize()
        );
    }

    /**
     * @test
     */
    public function it_can_deserialize_from_an_array(): void
    {
        $audienceUpdated = AudienceUpdated::deserialize(
            [
                'item_id' => $this->itemId,
                'audience' => ['audienceType' => 'members'],
            ]
        );

        $this->assertEquals($this->audienceUpdated, $audienceUpdated);
    }
}
