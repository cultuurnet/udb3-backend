<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\TestCase;

final class UpdateMainImageTest extends TestCase
{
    private UpdateMainImage $updateMainImage;

    protected function setUp(): void
    {
        $this->updateMainImage = new UpdateMainImage(
            '36a5ab5e-042a-48df-8609-93fce2195be8',
            new Uuid('10652dd4-e38d-4ade-a397-9e45b27f40fb')
        );
    }

    /**
     * @test
     */
    public function it_stores_an_organizer_id(): void
    {
        $this->assertEquals('36a5ab5e-042a-48df-8609-93fce2195be8', $this->updateMainImage->getItemId());
    }

    /**
     * @test
     */
    public function it_stores_an_image_id(): void
    {
        $this->assertEquals(
            new Uuid('10652dd4-e38d-4ade-a397-9e45b27f40fb'),
            $this->updateMainImage->getImageId()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_permission(): void
    {
        $this->assertEquals(Permission::organisatiesBewerken(), $this->updateMainImage->getPermission());
    }
}
