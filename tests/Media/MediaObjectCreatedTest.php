<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

use CultuurNet\UDB3\Media\Events\MediaObjectCreated;
use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use PHPUnit\Framework\TestCase;

final class MediaObjectCreatedTest extends TestCase
{
    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_should_include_all_properties_when_serializing(
        array $expectedSerializedValue,
        MediaObjectCreated $mediaObjectCreated
    ): void {
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
        array $serializedValue,
        MediaObjectCreated $expectedMediaObjectCreated
    ): void {
        $this->assertEquals(
            $expectedMediaObjectCreated,
            MediaObjectCreated::deserialize($serializedValue)
        );
    }

    public function serializationDataProvider(): array
    {
        return [
            'creationEvent' => [
                [
                    'media_object_id' => 'de305d54-75b4-431b-adb2-eb6b9e546014',
                    'mime_type' => 'image/png',
                    'description' => 'The Gleaners',
                    'copyright_holder' => 'Jean-François Millet',
                    'source_location' => 'http://foo.be/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                    'language' => 'en',
                ],
                new MediaObjectCreated(
                    new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
                    new MIMEType('image/png'),
                    new Description('The Gleaners'),
                    new CopyrightHolder('Jean-François Millet'),
                    new Url('http://foo.be/de305d54-75b4-431b-adb2-eb6b9e546014.png'),
                    new Language('en')
                ),
            ],
        ];
    }

    /**
     * @test
     */
    public function it_should_default_to_dutch_when_deserializing_event_data_without_language(): void
    {
        $eventData = [
            'media_object_id' => 'de305d54-75b4-431b-adb2-eb6b9e546014',
            'mime_type' => 'image/png',
            'description' => 'The Gleaners',
            'copyright_holder' => 'Jean-François Millet',
            'source_location' => 'http://foo.be/de305d54-75b4-431b-adb2-eb6b9e546014.png',
        ];

        $expectedEvent = new MediaObjectCreated(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/png'),
            new Description('The Gleaners'),
            new CopyrightHolder('Jean-François Millet'),
            new Url('http://foo.be/de305d54-75b4-431b-adb2-eb6b9e546014.png'),
            new Language('nl')
        );

        $this->assertEquals(MediaObjectCreated::deserialize($eventData), $expectedEvent);
    }

    /**
     * @test
     */
    public function it_can_create_a_media_object_from_serialized_data_with_invalid_length_copyright_holder(): void
    {
        $eventData = [
            'media_object_id' => 'de305d54-75b4-431b-adb2-eb6b9e546014',
            'mime_type' => 'image/png',
            'description' => 'The Gleaners',
            'copyright_holder' => 'J',
            'source_location' => 'http://foo.be/de305d54-75b4-431b-adb2-eb6b9e546014.png',
        ];

        $expectedEvent = new MediaObjectCreated(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/png'),
            new Description('The Gleaners'),
            new CopyrightHolder('J_'),
            new Url('http://foo.be/de305d54-75b4-431b-adb2-eb6b9e546014.png'),
            new Language('nl')
        );

        $this->assertEquals(MediaObjectCreated::deserialize($eventData), $expectedEvent);
    }
}
