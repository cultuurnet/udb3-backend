<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\BookingInfo;
use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\ValueObject\MultilingualString;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AbstractUpdateBookingInfoTest extends TestCase
{
    /**
     * @var AbstractUpdateBookingInfo&MockObject
     */
    protected $updateBookingInfo;

    /**
     * @var string
     */
    protected $itemId;

    /**
     * @var BookingInfo
     */
    protected $bookingInfo;

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

        $this->updateBookingInfo = $this->getMockForAbstractClass(
            AbstractUpdateBookingInfo::class,
            [$this->itemId, $this->bookingInfo]
        );
    }

    /**
     * @test
     */
    public function it_can_return_its_properties(): void
    {
        $bookingInfo = $this->updateBookingInfo->getBookingInfo();
        $expectedBookingInfo = new BookingInfo(
            'http://foo.bar',
            new MultilingualString(new Language('nl'), 'urlLabel'),
            '0123456789',
            'foo@bar.com',
            DateTimeFactory::fromAtom('2016-01-01T00:00:00+01:00'),
            DateTimeFactory::fromAtom('2016-01-31T00:00:00+01:00')
        );

        $this->assertEquals($expectedBookingInfo, $bookingInfo);

        $itemId = $this->updateBookingInfo->getItemId();
        $expectedItemId = 'Foo';

        $this->assertEquals($expectedItemId, $itemId);
    }
}
