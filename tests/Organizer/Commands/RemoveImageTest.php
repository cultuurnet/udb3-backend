<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use PHPUnit\Framework\TestCase;

final class RemoveImageTest extends TestCase
{
    private RemoveImage $removeImage;

    protected function setUp(): void
    {
        $this->removeImage = new RemoveImage(
            '683739ce-f048-438d-8131-a674286c0b2f',
            new UUID('ac4cd69f-c789-41c9-aa85-e21c8e481f58')
        );
    }

    /**
     * @test
     */
    public function it_stores_an_organizer_id(): void
    {
        $this->assertEquals(
            '683739ce-f048-438d-8131-a674286c0b2f',
            $this->removeImage->getOrganizerId()
        );
    }

    /**
     * @test
     */
    public function it_stores_an_image_id(): void
    {
        $this->assertEquals(
            new UUID('ac4cd69f-c789-41c9-aa85-e21c8e481f58'),
            $this->removeImage->getImageId()
        );
    }
}
