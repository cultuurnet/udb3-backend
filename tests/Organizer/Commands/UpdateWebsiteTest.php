<?php

namespace CultuurNet\UDB3\Organizer\Commands;

use PHPUnit\Framework\TestCase;
use ValueObjects\Web\Url;

class UpdateWebsiteTest extends TestCase
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
     * @var UpdateWebsite
     */
    private $updateWebsite;

    protected function setUp()
    {
        $this->organizerId = '8f9f5180-1099-474e-804c-461fc3701e5c';

        $this->website = Url::fromNative('http://www.company.be');

        $this->updateWebsite = new UpdateWebsite(
            $this->organizerId,
            $this->website
        );
    }

    /**
     * @test
     */
    public function it_stores_an_organizer_id()
    {
        $this->assertEquals(
            $this->organizerId,
            $this->updateWebsite->getOrganizerId()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_url()
    {
        $this->assertEquals(
            $this->website,
            $this->updateWebsite->getWebsite()
        );
    }
}
