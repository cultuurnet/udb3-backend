<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\BookingInfo;
use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\ValueObject\MultilingualString;
use PHPUnit\Framework\TestCase;

class BookingInfoUpdatedTest extends TestCase
{
    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_serialized_into_an_array(
        array $expectedSerializedValue,
        BookingInfoUpdated $bookingInfoUpdated
    ): void {
        $this->assertEquals(
            $expectedSerializedValue,
            $bookingInfoUpdated->serialize()
        );
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_deserialized_from_an_array(
        array $serializedValue,
        BookingInfoUpdated $expectedBookingInfoUpdated
    ): void {
        $this->assertEquals(
            $expectedBookingInfoUpdated,
            BookingInfoUpdated::deserialize($serializedValue)
        );
    }

    public function serializationDataProvider(): array
    {
        return [
            'bookingInfoUpdated' => [
                [
                    'item_id' => 'foo',
                    'bookingInfo' => [
                        'phone' => '0123456789',
                        'email' => 'foo@bar.com',
                        'url' => 'http://foo.bar',
                        'urlLabel' => ['nl' => 'urlLabel'],
                        'availabilityStarts' => '2016-01-01T00:00:00+01:00',
                        'availabilityEnds' => '2016-01-31T00:00:00+01:00',
                    ],
                ],
                new BookingInfoUpdated(
                    'foo',
                    new BookingInfo(
                        'http://foo.bar',
                        new MultilingualString(new Language('nl'), 'urlLabel'),
                        '0123456789',
                        'foo@bar.com',
                        DateTimeFactory::fromAtom('2016-01-01T00:00:00+01:00'),
                        DateTimeFactory::fromAtom('2016-01-31T00:00:00+01:00')
                    )
                ),
            ],
            'bookingInfoDeleted' => [
                [
                    'item_id' => 'foo',
                    'bookingInfo' => [],
                ],
                new BookingInfoUpdated(
                    'foo',
                    new BookingInfo()
                ),
            ],
        ];
    }
}
