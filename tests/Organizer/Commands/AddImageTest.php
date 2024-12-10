<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Image;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\TestCase;

final class AddImageTest extends TestCase
{
    private AddImage $addImage;

    protected function setUp(): void
    {
        $this->addImage = new AddImage(
            '437604d2-5cb6-44ed-bb10-92ce33b6e7bd',
            new Image(
                new Uuid('cf539408-bba9-4e77-9f85-72019013db37'),
                new Language('nl'),
                new Description('Description of the image'),
                new CopyrightHolder('publiq')
            )
        );
    }

    /**
     * @test
     */
    public function it_stores_an_organizer_id(): void
    {
        $this->assertEquals('437604d2-5cb6-44ed-bb10-92ce33b6e7bd', $this->addImage->getItemId());
    }

    /**
     * @test
     */
    public function it_stores_an_image(): void
    {
        $this->assertEquals(
            new Image(
                new Uuid('cf539408-bba9-4e77-9f85-72019013db37'),
                new Language('nl'),
                new Description('Description of the image'),
                new CopyrightHolder('publiq')
            ),
            $this->addImage->getImage()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_permission(): void
    {
        $this->assertEquals(Permission::organisatiesBewerken(), $this->addImage->getPermission());
    }
}
