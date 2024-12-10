<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use PHPUnit\Framework\TestCase;

class ImageTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_serialized(): void
    {
        $image = new Image(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/jpg'),
            new Description('my pic'),
            new CopyrightHolder('Dirk Dirkington'),
            new Url('http://foo.bar/media/my_pic.jpg'),
            new Language('en')
        );

        $serializedImage = $image->serialize();
        $expectedSerializedImage = [
            'media_object_id' => 'de305d54-75b4-431b-adb2-eb6b9e546014',
            'mime_type' => 'image/jpg',
            'description' => 'my pic',
            'copyright_holder' => 'Dirk Dirkington',
            'source_location' => 'http://foo.bar/media/my_pic.jpg',
            'language' => 'en',
        ];

        $this->assertEquals($expectedSerializedImage, $serializedImage);
    }

    /**
     * @test
     */
    public function it_can_create_an_image_from_serialized_data(): void
    {
        $serializedData = [
            'media_object_id' => 'de305d54-75b4-431b-adb2-eb6b9e546014',
            'mime_type' => 'image/jpg',
            'description' => 'my pic',
            'copyright_holder' => 'Dirk Dirkington',
            'source_location' => 'http://foo.bar/media/my_pic.jpg',
            'language' => 'en',
        ];
        $image = Image::deserialize($serializedData);
        $expectedImage = new Image(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/jpg'),
            new Description('my pic'),
            new CopyrightHolder('Dirk Dirkington'),
            new Url('http://foo.bar/media/my_pic.jpg'),
            new Language('en')
        );

        $this->assertEquals($expectedImage, $image);
    }

    /**
     * @test
     */
    public function it_can_create_an_image_from_serialized_data_with_invalid_length_copyright_holder(): void
    {
        $serializedData = [
            'media_object_id' => 'de305d54-75b4-431b-adb2-eb6b9e546014',
            'mime_type' => 'image/jpg',
            'description' => 'my pic',
            'copyright_holder' => '1',
            'source_location' => 'http://foo.bar/media/my_pic.jpg',
            'language' => 'en',
        ];

        $image = Image::deserialize($serializedData);

        $expectedImage = new Image(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/jpg'),
            new Description('my pic'),
            new CopyrightHolder('1_'),
            new Url('http://foo.bar/media/my_pic.jpg'),
            new Language('en')
        );

        $this->assertEquals($expectedImage, $image);
    }

    /**
     * @test
     */
    public function it_should_default_to_dutch_when_deserializing_image_data_without_a_language(): void
    {
        $serializedData = [
            'media_object_id' => 'de305d54-75b4-431b-adb2-eb6b9e546014',
            'mime_type' => 'image/jpg',
            'description' => 'my pic',
            'copyright_holder' => 'Dirk Dirkington',
            'source_location' => 'http://foo.bar/media/my_pic.jpg',
        ];
        $image = Image::deserialize($serializedData);
        $expectedImage = new Image(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/jpg'),
            new Description('my pic'),
            new CopyrightHolder('Dirk Dirkington'),
            new Url('http://foo.bar/media/my_pic.jpg'),
            new Language('nl')
        );

        $this->assertEquals($expectedImage, $image);
    }
}
