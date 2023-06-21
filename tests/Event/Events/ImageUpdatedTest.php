<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use PHPUnit\Framework\TestCase;

class ImageUpdatedTest extends TestCase
{
    /**
     * @test
     * @dataProvider eventDataProvider
     * @param array $expectedSerializedValue
     */
    public function it_can_be_serialized_into_an_array(
        $expectedSerializedValue,
        ImageUpdated $imageUpdated
    ): void {
        $this->assertEquals(
            $expectedSerializedValue,
            $imageUpdated->serialize()
        );
    }

    /**
     * @test
     * @dataProvider eventDataProvider
     * @param array $serializedValue
     */
    public function it_can_be_deserialized_from_an_array(
        $serializedValue,
        ImageUpdated $expectedImageUpdated
    ): void {
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
                    'ea305d54-75b4-431b-adb2-eb6b9e546019',
                    'some description',
                    'Dirk'
                ),
            ],
        ];
    }
}
