<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\BookingInfo;
use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Model\ValueObject\Contact\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumber;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\TranslatedWebsiteLabel;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Model\ValueObject\Web\WebsiteLabel;
use CultuurNet\UDB3\Model\ValueObject\Web\WebsiteLink;
use PHPUnit\Framework\TestCase;

class UpdateBookingInfoTest extends TestCase
{
    protected UpdateBookingInfo $updateBookingInfo;

    public function setUp(): void
    {
        $this->updateBookingInfo = new UpdateBookingInfo(
            'id',
            new BookingInfo(
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
            )
        );
    }

    /**
     * @test
     */
    public function it_is_possible_to_instantiate_the_command_with_parameters(): void
    {
        $expectedUpdateBookingInfo = new UpdateBookingInfo(
            'id',
            new BookingInfo(
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
            )
        );

        $this->assertEquals($expectedUpdateBookingInfo, $this->updateBookingInfo);
    }
}
