<?php

namespace CultuurNet\UDB3\Organizer\Events;

use PHPUnit\Framework\TestCase;
use ValueObjects\Web\Url;

class WebsiteUpdatedTest extends TestCase
{
    /**
     * @var string
     */
    private $organizerId;

    /**
     * @var Url
     */
    private $website;

    /**
     * @var WebsiteUpdated
     */
    private $websiteUpdated;

    /**
     * @var array
     */
    private $websiteUpdatedAsArray;

    protected function setUp()
    {
        $this->organizerId = '11cab069-7355-4fbc-bb82-eef9edfd7788';

        $this->website = Url::fromNative('http://www.depot.be');

        $this->websiteUpdated = new WebsiteUpdated(
            $this->organizerId,
            $this->website
        );

        $this->websiteUpdatedAsArray = [
            'organizer_id' =>  $this->organizerId,
            'website' => (string) $this->website,
        ];
    }

    /**
     * @test
     */
    public function it_can_serialize_to_an_array()
    {
        $this->assertEquals(
            $this->websiteUpdatedAsArray,
            $this->websiteUpdated->serialize()
        );
    }

    /**
     * @test
     */
    public function it_can_deserialize_from_an_array()
    {
        $this->assertEquals(
            WebsiteUpdated::deserialize($this->websiteUpdatedAsArray),
            $this->websiteUpdated
        );
    }
}
