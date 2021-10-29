<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\ContactPoint;
use PHPUnit\Framework\TestCase;

final class UpdateContactPointTest extends TestCase
{
    private string $organizerId;

    private ContactPoint $contactPoint;

    private UpdateContactPoint $updateContactPoint;

    protected function setUp(): void
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
    public function it_stores_an_organizer_id(): void
    {
        $this->assertEquals(
            $this->organizerId,
            $this->updateContactPoint->getItemId()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_contact_point(): void
    {
        $this->assertEquals(
            $this->contactPoint,
            $this->updateContactPoint->getContactPoint()
        );
    }
}
