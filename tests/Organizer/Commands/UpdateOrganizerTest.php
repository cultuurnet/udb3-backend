<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\TestCase;

final class UpdateOrganizerTest extends TestCase
{
    private UpdateOrganizer $updateOrganizer;

    protected function setUp(): void
    {
        $this->updateOrganizer = new UpdateOrganizer('36a5ab5e-042a-48df-8609-93fce2195be8');
    }

    /**
     * @test
     */
    public function it_stores_an_organizer_id(): void
    {
        $this->assertEquals('36a5ab5e-042a-48df-8609-93fce2195be8', $this->updateOrganizer->getItemId());
    }

    /**
     * @test
     */
    public function it_stores_an_optional_main_image_id(): void
    {
        $updateOrganizer = $this->updateOrganizer->withMainImageId(
            new UUID('10652dd4-e38d-4ade-a397-9e45b27f40fb')
        );

        $this->assertEquals(new UUID('10652dd4-e38d-4ade-a397-9e45b27f40fb'), $updateOrganizer->getMainImageId());
    }

    /**
     * @test
     */
    public function it_stores_a_permission(): void
    {
        $this->assertEquals(Permission::organisatiesBewerken(), $this->updateOrganizer->getPermission());
    }
}
