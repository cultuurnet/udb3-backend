<?php

namespace CultuurNet\UDB3\Event\Events;

use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

class ImageUpdatedTest extends TestCase
{
    /**
     * @test
     * @dataProvider eventDataProvider
     * @param array $expectedSerializedValue
     * @param ImageUpdated $imageUpdated
     */
    public function it_can_be_serialized_into_an_array(
        $expectedSerializedValue,
        ImageUpdated $imageUpdated
    ) {
        $this->assertEquals(
            $expectedSerializedValue,
            $imageUpdated->serialize()
        );
    }

    /**
     * @test
     * @dataProvider eventDataProvider
     * @param array $serializedValue
     * @param ImageUpdated $expectedImageUpdated
     */
    public function it_can_be_deserialized_from_an_array(
        $serializedValue,
        ImageUpdated $expectedImageUpdated
    ) {
        $this->assertEquals(
            $expectedImageUpdated,
            ImageUpdated::deserialize($serializedValue)
        );
    }

    public function eventDataProvider()
    {
        return [
            'imageUpdated' => [
                [
                    'item_id' => 'de305d54-75b4-431b-adb2-eb6b9e546014',
                    'media_object_id' => 'ea305d54-75b4-431b-adb2-eb6b9e546019',
                    'description' => 'some description',
                    'copyright_holder' => 'Dirk',
                ],
                new ImageUpdated(
                    'de305d54-75b4-431b-adb2-eb6b9e546014',
                    new UUID('ea305d54-75b4-431b-adb2-eb6b9e546019'),
                    new StringLiteral('some description'),
                    new StringLiteral('Dirk')
                ),
            ],
        ];
    }
}
