<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

use PHPUnit\Framework\TestCase;

final class ImageAddedTest extends TestCase
{
    private ImageAdded $imageAdded;

    protected function setUp(): void
    {
        $this->imageAdded = new ImageAdded(
            '688cc29c-6662-4fc0-b832-71adf5282130',
            '78957a0a-74ad-415d-ab6b-f49b865957fd',
            'en',
            'Description of the image',
            'Copyright holder of the image'
        );
    }

    /**
     * @test
     */
    public function it_has_an_organizer_id(): void
    {
        $this->assertEquals('688cc29c-6662-4fc0-b832-71adf5282130', $this->imageAdded->getOrganizerId());
    }

    /**
     * @test
     */
    public function it_has_an_image_id(): void
    {
        $this->assertEquals('78957a0a-74ad-415d-ab6b-f49b865957fd', $this->imageAdded->getImageId());
    }

    /**
     * @test
     */
    public function it_has_a_language(): void
    {
        $this->assertEquals('en', $this->imageAdded->getLanguage());
    }

    /**
     * @test
     */
    public function it_has_a_description(): void
    {
        $this->assertEquals('Description of the image', $this->imageAdded->getDescription());
    }

    /**
     * @test
     */
    public function it_has_a_copyright_holder(): void
    {
        $this->assertEquals('Copyright holder of the image', $this->imageAdded->getCopyrightHolder());
    }

    /**
     * @test
     */
    public function it_can_serialize(): void
    {
        $this->assertEquals(
            [
                'organizerId' => '688cc29c-6662-4fc0-b832-71adf5282130',
                'imageId' => '78957a0a-74ad-415d-ab6b-f49b865957fd',
                'language' => 'en',
                'description' => 'Description of the image',
                'copyrightHolder' => 'Copyright holder of the image',
            ],
            $this->imageAdded->serialize()
        );
    }
}
