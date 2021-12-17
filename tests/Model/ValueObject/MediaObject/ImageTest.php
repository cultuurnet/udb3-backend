<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\MediaObject;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use PHPUnit\Framework\TestCase;

final class ImageTest extends TestCase
{
    private Image $image;

    protected function setUp(): void
    {
        $this->image = new Image(
            new UUID('cf539408-bba9-4e77-9f85-72019013db37'),
            new Language('nl'),
            new Description('Description of the image'),
            new CopyrightHolder('publiq')
        );
    }

    /**
     * @test
     */
    public function it_stores_an_id(): void
    {
        $this->assertEquals(new UUID('cf539408-bba9-4e77-9f85-72019013db37'), $this->image->getId());
    }

    /**
     * @test
     */
    public function it_stores_a_language(): void
    {
        $this->assertEquals(new Language('nl'), $this->image->getLanguage());
    }

    /**
     * @test
     */
    public function it_stores_a_description(): void
    {
        $this->assertEquals(new Description('Description of the image'), $this->image->getDescription());
    }

    /**
     * @test
     */
    public function it_stores_a_copyright_holder(): void
    {
        $this->assertEquals(new CopyrightHolder('publiq'), $this->image->getCopyrightHolder());
    }
}
