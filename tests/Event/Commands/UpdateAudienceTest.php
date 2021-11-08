<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\Model\ValueObject\Audience\AudienceType;
use PHPUnit\Framework\TestCase;

class UpdateAudienceTest extends TestCase
{
    private AudienceType $audienceType;

    private UpdateAudience $updateAudience;

    protected function setUp(): void
    {
        $this->audienceType = AudienceType::education();

        $this->updateAudience = new UpdateAudience(
            '6eaaa9b6-d0d2-11e6-bf26-cec0c932ce01',
            $this->audienceType
        );
    }

    /**
     * @test
     */
    public function it_should_identify_the_event_to_update_by_item_id(): void
    {
        $this->assertEquals('6eaaa9b6-d0d2-11e6-bf26-cec0c932ce01', $this->updateAudience->getItemId());
    }

    /**
     * @test
     */
    public function it_stores_an_audience_type(): void
    {
        $this->assertEquals(
            $this->audienceType,
            $this->updateAudience->getAudienceType()
        );
    }
}
