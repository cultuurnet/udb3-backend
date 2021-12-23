<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Organizer\Events;

use CultuurNet\UDB3\Organizer\Events\OrganizerUpdated;
use PHPUnit\Framework\TestCase;

final class OrganizerUpdatedTest extends TestCase
{
    private OrganizerUpdated $organizerUpdated;

    protected function setUp(): void
    {
        $this->organizerUpdated = new OrganizerUpdated('36a5ab5e-042a-48df-8609-93fce2195be8');
    }

    /**
     * @test
     */
    public function it_stores_an_organizer_id(): void
    {
        $this->assertEquals('36a5ab5e-042a-48df-8609-93fce2195be8', $this->organizerUpdated->getOrganizerId());
    }

    /**
     * @test
     */
    public function it_stores_an_optional_main_image_id(): void
    {
        $organizerUpdated = $this->organizerUpdated->withMainImageId('10652dd4-e38d-4ade-a397-9e45b27f40fb');

        $this->assertEquals('10652dd4-e38d-4ade-a397-9e45b27f40fb', $organizerUpdated->getMainImageId());
    }

    /**
     * @test
     * @dataProvider serializeDataProvider
     */
    public function it_can_serialize(array $organizerUpdatedAsArray, OrganizerUpdated $organizerUpdated): void
    {
        $this->assertEquals(
            $organizerUpdatedAsArray,
            $organizerUpdated->serialize()
        );

        $this->assertEquals(
            $organizerUpdated,
            OrganizerUpdated::deserialize($organizerUpdatedAsArray)
        );
    }

    public function serializeDataProvider(): array
    {
        return [
            'Only organizer id' => [
                [
                    'organizerId' => '36a5ab5e-042a-48df-8609-93fce2195be8',
                ],
                new OrganizerUpdated('36a5ab5e-042a-48df-8609-93fce2195be8'),
            ],
            'With main image id' => [
                [
                    'organizerId' => '36a5ab5e-042a-48df-8609-93fce2195be8',
                    'mainImageId' => '10652dd4-e38d-4ade-a397-9e45b27f40fb',
                ],
                (new OrganizerUpdated('36a5ab5e-042a-48df-8609-93fce2195be8'))
                    ->withMainImageId('10652dd4-e38d-4ade-a397-9e45b27f40fb'),
            ],
        ];
    }
}
