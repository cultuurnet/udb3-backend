<?php

namespace CultuurNet\UDB3\Place\Events;

use PHPUnit\Framework\TestCase;

final class MarkedAsCanonicalTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_be_serializable_and_deserializable()
    {
        $event = new MarkedAsCanonical(
            'a9088117-5ec8-4117-8ce0-5ce27e685055',
            '7ee54099-9e0f-4c55-9a28-b548ef2a41ba',
            [
                '4de29c86-6c91-4fe4-81e8-167dbcae3de8',
                'f199bd45-b40f-4cd9-bd15-7d302f8935ab',
            ]
        );

        $eventAsArray = [
            'place_id' => 'a9088117-5ec8-4117-8ce0-5ce27e685055',
            'duplicated_by' => '7ee54099-9e0f-4c55-9a28-b548ef2a41ba',
            'duplicates_of_duplicate' => [
                '4de29c86-6c91-4fe4-81e8-167dbcae3de8',
                'f199bd45-b40f-4cd9-bd15-7d302f8935ab',
            ],
        ];

        $serializedEvent = $event->serialize();
        $this->assertEquals($eventAsArray, $serializedEvent);

        $deserializedEvent = MarkedAsCanonical::deserialize($eventAsArray);
        $this->assertEquals($event, $deserializedEvent);
    }
}
