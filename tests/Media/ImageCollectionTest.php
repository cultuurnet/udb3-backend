<?php

namespace CultuurNet\UDB3\Media;

use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\Properties\CopyrightHolder;
use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Url;

class ImageCollectionTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_remember_the_main_image()
    {
        $mainImage = new Image(
            new UUID(),
            MIMEType::fromSubtype('jpeg'),
            new Description('my best selfie'),
            new CopyrightHolder('Henk'),
            Url::fromNative('http://du.de/images/henk_032.jpg'),
            new Language('en')
        );
        $images = (new ImageCollection())->withMain($mainImage);

        $this->assertEquals($mainImage, $images->getMain());
    }

    /**
     * @test
     */
    public function it_should_return_the_first_image_as_main_when_set_explicitly()
    {
        $image = new Image(
            new UUID(),
            MIMEType::fromSubtype('jpeg'),
            new Description('my best selfie'),
            new CopyrightHolder('Henk'),
            Url::fromNative('http://du.de/images/henk_032.jpg'),
            new Language('en')
        );
        $images = (new ImageCollection())->with($image);

        $this->assertEquals($image, $images->getMain());
    }

    /**
     * @test
     */
    public function it_should_return_a_main_image_when_empty()
    {
        $this->assertEquals(null, (new ImageCollection())->getMain());
    }

    /**
     * @test
     */
    public function it_can_find_an_image_based_on_uuid()
    {
        $uuid = new UUID();

        $image = new Image(
            $uuid,
            MIMEType::fromSubtype('jpeg'),
            new Description('my best selfie'),
            new CopyrightHolder('Henk'),
            Url::fromNative('http://du.de/images/henk_032.jpg'),
            new Language('en')
        );

        $anotherImage = new Image(
            new UUID(),
            MIMEType::fromSubtype('jpeg'),
            new Description('world biggest cat'),
            new CopyrightHolder('Doggy Junier'),
            Url::fromNative('http://www.cats.com/biggest.jpeg'),
            new Language('en')
        );

        $images = (new ImageCollection())
            ->with($image)
            ->with($anotherImage);

        $this->assertEquals($image, $images->findImageByUUID($uuid));
        $this->assertNull($images->findImageByUUID(new UUID()));
    }
}
