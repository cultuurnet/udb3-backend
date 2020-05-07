<?php

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\Event\ValueObjects\Audience;
use CultuurNet\UDB3\Event\ValueObjects\AudienceType;
use PHPUnit\Framework\TestCase;

class UpdateAudienceTest extends TestCase
{
    /**
     * @var AudienceType
     */
    private $audienceType;

    /**
     * @var Audience
     */
    private $audience;

    /**
     * @var UpdateAudience
     */
    private $updateAudience;

    protected function setUp()
    {
        $this->audienceType = AudienceType::EDUCATION();

        $this->audience = new Audience($this->audienceType);

        $this->updateAudience = new UpdateAudience(
            '6eaaa9b6-d0d2-11e6-bf26-cec0c932ce01',
            $this->audience
        );
    }

    /**
     * @test
     */
    public function it_should_identify_the_event_to_update_by_item_id()
    {
        $this->assertEquals('6eaaa9b6-d0d2-11e6-bf26-cec0c932ce01', $this->updateAudience->getItemId());
    }

    /**
     * @test
     */
    public function it_stores_an_audience_type()
    {
        $this->assertEquals(
            $this->audience,
            $this->updateAudience->getAudience()
        );
    }
}
