<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

use PHPUnit\Framework\TestCase;

final class MainImageUpdatedTest extends TestCase
{
    private MainImageUpdated $mainImageUpdated;

    protected function setUp(): void
    {
        $this->mainImageUpdated = new MainImageUpdated(
            '36a5ab5e-042a-48df-8609-93fce2195be8',
            '10652dd4-e38d-4ade-a397-9e45b27f40fb'
        );
    }

    /**
     * @test
     */
    public function it_stores_an_organizer_id(): void
    {
        $this->assertEquals('36a5ab5e-042a-48df-8609-93fce2195be8', $this->mainImageUpdated->getOrganizerId());
    }

    /**
     * @test
     */
    public function it_stores_an_optional_main_image_id(): void
    {
        $this->assertEquals('10652dd4-e38d-4ade-a397-9e45b27f40fb', $this->mainImageUpdated->getMainImageId());
    }

    /**
     * @test
     */
    public function it_can_serialize(): void
    {
        $this->assertEquals(
            [
                'organizerId' => '36a5ab5e-042a-48df-8609-93fce2195be8',
                'mainImageId' => '10652dd4-e38d-4ade-a397-9e45b27f40fb',
            ],
            $this->mainImageUpdated->serialize()
        );
    }

    /**
     * @test
     */
    public function it_can_deserialize(): void
    {
        $this->assertEquals(
            $this->mainImageUpdated,
            MainImageUpdated::deserialize(
                [
                    'organizerId' => '36a5ab5e-042a-48df-8609-93fce2195be8',
                    'mainImageId' => '10652dd4-e38d-4ade-a397-9e45b27f40fb',
                ]
            )
        );
    }
}
