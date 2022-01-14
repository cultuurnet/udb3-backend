<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

use PHPUnit\Framework\TestCase;

final class DescriptionDeletedTest extends TestCase
{
    private DescriptionDeleted $descriptionDeleted;

    protected function setUp(): void
    {
        $this->descriptionDeleted = new DescriptionDeleted(
            'f6549ff4-aafc-436e-8630-48cd05a01943',
            'nl'
        );
    }

    /**
     * @test
     */
    public function it_stores_an_organizer_id(): void
    {
        $this->assertEquals('f6549ff4-aafc-436e-8630-48cd05a01943', $this->descriptionDeleted->getOrganizerId());
    }

    /**
     * @test
     */
    public function it_stores_a_language(): void
    {
        $this->assertEquals('nl', $this->descriptionDeleted->getLanguage());
    }

    /**
     * @test
     */
    public function it_can_serialize(): void
    {
        $this->assertEquals(
            [
                'organizerId' => 'f6549ff4-aafc-436e-8630-48cd05a01943',
                'language' => 'nl',
            ],
            $this->descriptionDeleted->serialize()
        );
    }

    /**
     * @test
     */
    public function it_can_deserialize(): void
    {
        $this->assertEquals(
            new DescriptionDeleted(
                'f6549ff4-aafc-436e-8630-48cd05a01943',
                'nl'
            ),
            DescriptionDeleted::deserialize(
                [
                    'organizerId' => 'f6549ff4-aafc-436e-8630-48cd05a01943',
                    'language' => 'nl',
                ]
            )
        );
    }
}
