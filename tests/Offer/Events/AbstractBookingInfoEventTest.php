<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Events;

use CultuurNet\UDB3\BookingInfo;
use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\ValueObject\MultilingualString;
use PHPUnit\Framework\TestCase;

class AbstractBookingInfoEventTest extends TestCase
{
    /**
     * @var AbstractBookingInfoEvent
     */
    protected $abstractBookingInfoEvent;

    protected string $itemId;

    protected BookingInfo $bookingInfo;

    public function setUp(): void
    {
        $this->itemId = 'Foo';
        $this->bookingInfo = new BookingInfo(
            'http://foo.bar',
            new MultilingualString(new Language('nl'), 'urlLabel'),
            '0123456789',
            'foo@bar.com',
            DateTimeFactory::fromAtom('2016-01-01T00:00:00+01:00'),
            DateTimeFactory::fromAtom('2016-01-31T00:00:00+01:00')
        );
        $this->abstractBookingInfoEvent = new MockAbstractBookingInfoEvent(
            $this->itemId,
            $this->bookingInfo
        );
    }

    /**
     * @test
     */
    public function it_can_be_instantiated_with_properties(): void
    {
        $expectedItemId = 'Foo';
        $expectedBookingInfo = new BookingInfo(
            'http://foo.bar',
            new MultilingualString(new Language('nl'), 'urlLabel'),
            '0123456789',
            'foo@bar.com',
            DateTimeFactory::fromAtom('2016-01-01T00:00:00+01:00'),
            DateTimeFactory::fromAtom('2016-01-31T00:00:00+01:00')
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
    public function it_can_return_its_properties(): void
    {
        $expectedItemId = 'Foo';
        $expectedBookingInfo = new BookingInfo(
            'http://foo.bar',
            new MultilingualString(new Language('nl'), 'urlLabel'),
            '0123456789',
            'foo@bar.com',
            DateTimeFactory::fromAtom('2016-01-01T00:00:00+01:00'),
            DateTimeFactory::fromAtom('2016-01-31T00:00:00+01:00')
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
    ): void {
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
    ): void {
        $this->assertEquals(
            $expectedBookingInfoEvent,
            MockAbstractBookingInfoEvent::deserialize($serializedValue)
        );
    }

    public function serializationDataProvider(): array
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
                        new MultilingualString(new Language('nl'), 'urlLabel'),
                        '0123456789',
                        'foo@bar.com',
                        DateTimeFactory::fromAtom('2016-01-01T00:00:00+01:00'),
                        DateTimeFactory::fromAtom('2016-01-31T00:00:00+01:00')
                    )
                ),
            ],
        ];
    }
}
