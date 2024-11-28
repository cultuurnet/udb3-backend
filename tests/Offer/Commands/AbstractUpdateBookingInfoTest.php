<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Model\ValueObject\Contact\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Contact\BookingInfo;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumber;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\TranslatedWebsiteLabel;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Model\ValueObject\Web\WebsiteLabel;
use CultuurNet\UDB3\Model\ValueObject\Web\WebsiteLink;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AbstractUpdateBookingInfoTest extends TestCase
{
    /**
     * @var AbstractUpdateBookingInfo&MockObject
     */
    protected $updateBookingInfo;

    protected string $itemId;

    protected BookingInfo $bookingInfo;

    public function setUp(): void
    {
        $this->itemId = 'Foo';
        $this->bookingInfo = new BookingInfo(
            new WebsiteLink(
                new Url('http://foo.bar'),
                new TranslatedWebsiteLabel(
                    new Language('nl'),
                    new WebsiteLabel('urlLabel')
                )
            ),
            new TelephoneNumber('0123456789'),
            new EmailAddress('foo@bar.com'),
            BookingAvailability::fromTo(
                DateTimeFactory::fromAtom('2016-01-01T00:00:00+01:00'),
                DateTimeFactory::fromAtom('2016-01-31T00:00:00+01:00')
            )
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
            new WebsiteLink(
                new Url('http://foo.bar'),
                new TranslatedWebsiteLabel(
                    new Language('nl'),
                    new WebsiteLabel('urlLabel')
                )
            ),
            new TelephoneNumber('0123456789'),
            new EmailAddress('foo@bar.com'),
            BookingAvailability::fromTo(
                DateTimeFactory::fromAtom('2016-01-01T00:00:00+01:00'),
                DateTimeFactory::fromAtom('2016-01-31T00:00:00+01:00')
            )
        );

        $this->assertEquals($expectedBookingInfo, $bookingInfo);

        $itemId = $this->updateBookingInfo->getItemId();
        $expectedItemId = 'Foo';

        $this->assertEquals($expectedItemId, $itemId);
    }
}
