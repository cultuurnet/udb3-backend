<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use PHPUnit\Framework\TestCase;

final class UpdateWebsiteTest extends TestCase
{
    private string $organizerId;

    private Url $website;

    private UpdateWebsite $updateWebsite;

    protected function setUp(): void
    {
        $this->organizerId = '8f9f5180-1099-474e-804c-461fc3701e5c';

        $this->website = new Url('http://www.company.be');

        $this->updateWebsite = new UpdateWebsite(
            $this->organizerId,
            $this->website
        );
    }

    /**
     * @test
     */
    public function it_stores_an_organizer_id(): void
    {
        $this->assertEquals(
            $this->organizerId,
            $this->updateWebsite->getItemId()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_url(): void
    {
        $this->assertEquals(
            $this->website,
            $this->updateWebsite->getWebsite()
        );
    }
}
