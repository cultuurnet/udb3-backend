<?php

namespace CultuurNet\UDB3\Offer\Events;

use CultuurNet\UDB3\BookingInfo;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\ValueObject\MultilingualString;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class AbstractBookingInfoEventTest extends TestCase
{
    /**
     * @var AbstractBookingInfoEvent
     */
    protected $abstractBookingInfoEvent;

    /**
     * @var string
     */
    protected $itemId;

    /**
     * @var BookingInfo
     */
    protected $bookingInfo;

    public function setUp()
    {
        $this->itemId = 'Foo';
        $this->bookingInfo = new BookingInfo(
            'http://foo.bar',
            new MultilingualString(new Language('nl'), new StringLiteral('urlLabel')),
            '0123456789',
            'foo@bar.com',
            \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2016-01-01T00:00:00+01:00'),
            \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2016-01-31T00:00:00+01:00')
        );
        $this->abstractBookingInfoEvent = new MockAbstractBookingInfoEvent(
            $this->itemId,
            $this->bookingInfo
        );
    }

    /**
     * @test
     */
    public function it_can_be_instantiated_with_properties()
    {
        $expectedItemId = 'Foo';
        $expectedBookingInfo = new BookingInfo(
            'http://foo.bar',
            new MultilingualString(new Language('nl'), new StringLiteral('urlLabel')),
            '0123456789',
            'foo@bar.com',
            \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2016-01-01T00:00:00+01:00'),
            \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2016-01-31T00:00:00+01:00')
        );
        $expectedAbstractBookingInfoEvent = new MockAbstractBookingInfoEvent(
            $expectedItemId,
            $expectedBookingInfo
        );

        $this->assertEquals($expectedAbstractBookingInfoEvent, $this->abstractBookingInfoEvent);
    }

    /**
     * @test
     */
    public function it_can_return_its_properties()
    {
        $expectedItemId = 'Foo';
        $expectedBookingInfo = new BookingInfo(
            'http://foo.bar',
            new MultilingualString(new Language('nl'), new StringLiteral('urlLabel')),
            '0123456789',
            'foo@bar.com',
            \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2016-01-01T00:00:00+01:00'),
            \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2016-01-31T00:00:00+01:00')
        );

        $itemId = $this->abstractBookingInfoEvent->getItemId();
        $bookingInfo = $this->abstractBookingInfoEvent->getBookingInfo();

        $this->assertEquals($expectedItemId, $itemId);
        $this->assertEquals($expectedBookingInfo, $bookingInfo);
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_serialized_to_an_array(
        array $expectedSerializedValue,
        MockAbstractBookingInfoEvent $bookingInfoEvent
    ) {
        $this->assertEquals(
            $expectedSerializedValue,
            $bookingInfoEvent->serialize()
        );
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_deserialize_an_array(
        array $serializedValue,
        MockAbstractBookingInfoEvent $expectedBookingInfoEvent
    ) {
        $this->assertEquals(
            $expectedBookingInfoEvent,
            MockAbstractBookingInfoEvent::deserialize($serializedValue)
        );
    }

    /**
     * @return array
     */
    public function serializationDataProvider()
    {
        return [
            'abstractBookingInfoEvent' => [
                [
                    'item_id' => 'madId',
                    'bookingInfo' => [
                        'phone' => '0123456789',
                        'email' => 'foo@bar.com',
                        'url' => 'http://foo.bar',
                        'urlLabel' => ['nl' => 'urlLabel'],
                        'availabilityStarts' => '2016-01-01T00:00:00+01:00',
                        'availabilityEnds' => '2016-01-31T00:00:00+01:00',
                    ],
                ],
                new MockAbstractBookingInfoEvent(
                    'madId',
                    new BookingInfo(
                        'http://foo.bar',
                        new MultilingualString(new Language('nl'), new StringLiteral('urlLabel')),
                        '0123456789',
                        'foo@bar.com',
                        \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2016-01-01T00:00:00+01:00'),
                        \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2016-01-31T00:00:00+01:00')
                    )
                ),
            ],
        ];
    }
}
