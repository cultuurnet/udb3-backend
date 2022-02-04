<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use PHPUnit\Framework\TestCase;
use ValueObjects\Web\Url;

class ImageCollectionTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_remember_the_main_image()
    {
        $mainImage = new Image(
            new UUID('7eae46b4-050b-4e8e-b796-c9b011b7279f'),
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
            new UUID('8c52f555-426b-46c6-87e5-31f2033c851a'),
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
        $uuid = new UUID('eed32a8c-cd07-4ade-93a2-7751d33c820c');

        $image = new Image(
            $uuid,
            MIMEType::fromSubtype('jpeg'),
            new Description('my best selfie'),
            new CopyrightHolder('Henk'),
            Url::fromNative('http://du.de/images/henk_032.jpg'),
            new Language('en')
        );

        $anotherImage = new Image(
            new UUID('2d0ec16f-752a-47cf-ab98-329c12025bb5'),
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
        $this->assertNull($images->findImageByUUID(new UUID('9f585fca-924f-46ea-aaf9-399b8f5aff51')));
    }
}
