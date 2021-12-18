<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

use PHPUnit\Framework\TestCase;

final class ImageRemovedTest extends TestCase
{
    private ImageRemoved $imageRemoved;

    protected function setUp(): void
    {
        $this->imageRemoved = new ImageRemoved(
            '683739ce-f048-438d-8131-a674286c0b2f',
            'ac4cd69f-c789-41c9-aa85-e21c8e481f58'
        );
    }

    /**
     * @test
     */
    public function it_stores_an_organizer_id(): void
    {
        $this->assertEquals(
            '683739ce-f048-438d-8131-a674286c0b2f',
            $this->imageRemoved->getOrganizerId()
        );
    }

    /**
     * @test
     */
    public function it_stores_an_image_id(): void
    {
        $this->assertEquals(
            'ac4cd69f-c789-41c9-aa85-e21c8e481f58',
            $this->imageRemoved->getImageId()
        );
    }

    /**
     * @test
     */
    public function it_can_be_serialized(): void
    {
        $this->assertEquals(
            [
                'organizerId' => '683739ce-f048-438d-8131-a674286c0b2f',
                'imageId' => 'ac4cd69f-c789-41c9-aa85-e21c8e481f58',
            ],
            $this->imageRemoved->serialize()
        );
    }

    /**
     * @test
     */
    public function it_can_be_deserialized(): void
    {
        $this->assertEquals(
            new ImageRemoved(
                '683739ce-f048-438d-8131-a674286c0b2f',
                'ac4cd69f-c789-41c9-aa85-e21c8e481f58'
            ),
            ImageRemoved::deserialize(
                [
                    'organizerId' => '683739ce-f048-438d-8131-a674286c0b2f',
                    'imageId' => 'ac4cd69f-c789-41c9-aa85-e21c8e481f58',
                ]
            )
        );
    }
}
