<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

use PHPUnit\Framework\TestCase;

final class ImageUpdatedTest extends TestCase
{
    private ImageUpdated $imageUpdated;

    protected function setUp(): void
    {
        $this->imageUpdated = new ImageUpdated(
            'a98df644-da7e-407e-9cd9-3217ddc61f27',
            '6da7230e-ce93-44d5-b76b-48a76140d8f7',
            'Updated copyright holder of the image',
            'Updated copyright holder'
        );
    }

    /**
     * @test
     */
    public function it_has_an_organizer_id(): void
    {
        $this->assertEquals('a98df644-da7e-407e-9cd9-3217ddc61f27', $this->imageUpdated->getOrganizerId());
    }

    /**
     * @test
     */
    public function it_has_an_image_id(): void
    {
        $this->assertEquals('6da7230e-ce93-44d5-b76b-48a76140d8f7', $this->imageUpdated->getImageId());
    }

    /**
     * @test
     */
    public function it_has_a_description(): void
    {
        $this->assertEquals('Updated copyright holder of the image', $this->imageUpdated->getDescription());
    }

    /**
     * @test
     */
    public function it_has_a_copyright_holder(): void
    {
        $this->assertEquals('Updated copyright holder', $this->imageUpdated->getCopyrightHolder());
    }

    /**
     * @test
     */
    public function it_can_serialize(): void
    {
        $this->assertEquals(
            [
                'organizerId' => 'a98df644-da7e-407e-9cd9-3217ddc61f27',
                'imageId' => '6da7230e-ce93-44d5-b76b-48a76140d8f7',
                'description' => 'Updated copyright holder of the image',
                'copyrightHolder' => 'Updated copyright holder',
            ],
            $this->imageUpdated->serialize()
        );
    }

    /**
     * @test
     */
    public function it_can_dedeserialize(): void
    {
        $this->assertEquals(
            $this->imageUpdated,
            ImageUpdated::deserialize(
                [
                    'organizerId' => 'a98df644-da7e-407e-9cd9-3217ddc61f27',
                    'imageId' => '6da7230e-ce93-44d5-b76b-48a76140d8f7',
                    'description' => 'Description of the image',
                    'copyrightHolder' => 'Copyright holder of the image',
                ]
            )
        );
    }
}
