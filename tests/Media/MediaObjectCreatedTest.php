<?php

namespace CultuurNet\UDB3\Media;

use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\Events\MediaObjectCreated;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Url;

class MediaObjectCreatedTest extends TestCase
{
    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_should_include_all_properties_when_serializing(
        $expectedSerializedValue,
        MediaObjectCreated $mediaObjectCreated
    ) {
        $this->assertEquals(
            $expectedSerializedValue,
            $mediaObjectCreated->serialize()
        );
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_should_set_all_the_properties_when_deserializing(
        $serializedValue,
        MediaObjectCreated $expectedMediaObjectCreated
    ) {
        $this->assertEquals(
            $expectedMediaObjectCreated,
            MediaObjectCreated::deserialize($serializedValue)
        );
    }

    public function serializationDataProvider()
    {
        return [
            'creationEvent' => [
                [
                    'media_object_id' => 'de305d54-75b4-431b-adb2-eb6b9e546014',
                    'mime_type' => 'image/png',
                    'description' => 'sexy ladies without clothes',
                    'copyright_holder' => 'Bart Ramakers',
                    'source_location' => 'http://foo.be/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                    'language' => 'en',
                ],
                new MediaObjectCreated(
                    new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
                    new MIMEType('image/png'),
                    new StringLiteral('sexy ladies without clothes'),
                    new StringLiteral('Bart Ramakers'),
                    Url::fromNative('http://foo.be/de305d54-75b4-431b-adb2-eb6b9e546014.png'),
                    new Language('en')
                ),
            ],
        ];
    }

    /**
     * @test
     */
    public function it_should_default_to_dutch_when_deserializing_event_data_without_language()
    {
        $eventData = [
            'media_object_id' => 'de305d54-75b4-431b-adb2-eb6b9e546014',
            'mime_type' => 'image/png',
            'description' => 'sexy ladies without clothes',
            'copyright_holder' => 'Bart Ramakers',
            'source_location' => 'http://foo.be/de305d54-75b4-431b-adb2-eb6b9e546014.png',
        ];

        $expectedEvent = new MediaObjectCreated(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/png'),
            new StringLiteral('sexy ladies without clothes'),
            new StringLiteral('Bart Ramakers'),
            Url::fromNative('http://foo.be/de305d54-75b4-431b-adb2-eb6b9e546014.png'),
            new Language('nl')
        );

        $this->assertEquals(MediaObjectCreated::deserialize($eventData), $expectedEvent);
    }
}
