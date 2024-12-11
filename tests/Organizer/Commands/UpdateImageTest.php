<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\TestCase;

final class UpdateImageTest extends TestCase
{
    private UpdateImage $updateImage;

    protected function setUp(): void
    {
        $this->updateImage = new UpdateImage(
            'a98df644-da7e-407e-9cd9-3217ddc61f27',
            new Uuid('6da7230e-ce93-44d5-b76b-48a76140d8f7')
        );
    }

    /**
     * @test
     */
    public function it_stores_an_organizer_id(): void
    {
        $this->assertEquals(
            'a98df644-da7e-407e-9cd9-3217ddc61f27',
            $this->updateImage->getItemId()
        );
    }

    /**
     * @test
     */
    public function it_stores_an_image_id(): void
    {
        $this->assertEquals(
            new Uuid('6da7230e-ce93-44d5-b76b-48a76140d8f7'),
            $this->updateImage->getImageId()
        );
    }

    /**
     * @test
     */
    public function it_has_an_optional_language(): void
    {
        $this->assertNull($this->updateImage->getLanguage());

        $updateImage = $this->updateImage->withLanguage(new Language('en'));

        $this->assertEquals(
            new Language('en'),
            $updateImage->getLanguage()
        );
    }

    /**
     * @test
     */
    public function it_has_an_optional_description(): void
    {
        $this->assertNull($this->updateImage->getDescription());

        $updateImage = $this->updateImage->withDescription(new Description('Image description'));

        $this->assertEquals(
            new Description('Image description'),
            $updateImage->getDescription()
        );
    }

    /**
     * @test
     */
    public function it_has_an_optional_copyright_holder(): void
    {
        $this->assertNull($this->updateImage->getCopyrightHolder());

        $updateImage = $this->updateImage->withCopyrightHolder(new CopyrightHolder('Copyright holder'));

        $this->assertEquals(
            new CopyrightHolder('Copyright holder'),
            $updateImage->getCopyrightHolder()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_permission(): void
    {
        $this->assertEquals(Permission::organisatiesBewerken(), $this->updateImage->getPermission());
    }
}
