<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

use PHPUnit\Framework\TestCase;

class WebsiteUpdatedTest extends TestCase
{
    private WebsiteUpdated $websiteUpdated;

    private array $websiteUpdatedAsArray;

    protected function setUp(): void
    {
        $organizerId = '11cab069-7355-4fbc-bb82-eef9edfd7788';

        $website = 'http://www.depot.be';

        $this->websiteUpdated = new WebsiteUpdated(
            $organizerId,
            $website
        );

        $this->websiteUpdatedAsArray = [
            'organizer_id' =>  $organizerId,
            'website' => $website,
        ];
    }

    /**
     * @test
     */
    public function it_can_serialize_to_an_array(): void
    {
        $this->assertEquals(
            $this->websiteUpdatedAsArray,
            $this->websiteUpdated->serialize()
        );
    }

    /**
     * @test
     */
    public function it_can_deserialize_from_an_array(): void
    {
        $this->assertEquals(
            WebsiteUpdated::deserialize($this->websiteUpdatedAsArray),
            $this->websiteUpdated
        );
    }
}
