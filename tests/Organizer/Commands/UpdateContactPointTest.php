<?php

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\ContactPoint;
use PHPUnit\Framework\TestCase;

class UpdateContactPointTest extends TestCase
{
    /**
     * @var string
     */
    private $organizerId;

    /**
     * @var ContactPoint
     */
    private $contactPoint;

    /**
     * @var UpdateContactPoint
     */
    private $updateContactPoint;

    protected function setUp()
    {
        $this->organizerId = 'c45b4f1a-7420-4f74-ab68-ff16d31b090c';

        $this->contactPoint = new ContactPoint(
            [
                [
                    '0123456789',
                ],
                [
                    'info@company.be',
                ],
                [
                    'www.company.be',
                ],
            ]
        );

        $this->updateContactPoint = new UpdateContactPoint(
            $this->organizerId,
            $this->contactPoint
        );
    }

    /**
     * @test
     */
    public function it_stores_an_organizer_id()
    {
        $this->assertEquals(
            $this->organizerId,
            $this->updateContactPoint->getOrganizerId()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_contact_point()
    {
        $this->assertEquals(
            $this->contactPoint,
            $this->updateContactPoint->getContactPoint()
        );
    }
}
