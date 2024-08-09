<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Contact;

use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\TranslatedWebsiteLabel;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Model\ValueObject\Web\WebsiteLabel;
use CultuurNet\UDB3\Model\ValueObject\Web\WebsiteLink;
use PHPUnit\Framework\TestCase;

class BookingInfoTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_be_creatable_without_any_info(): void
    {
        $bookingInfo = new BookingInfo();
        $this->assertTrue($bookingInfo->isEmpty());
    }

    /**
     * @test
     */
    public function it_should_return_copy_with_an_updated_website(): void
    {
        $website = $this->getWebsiteLink();
        $bookingInfo = $this->getBookingInfo();

        $newWebsiteLabel = $this->getWebsiteLabel()
            ->withTranslation(new Language('fr'), new WebsiteLabel('Google FR'));

        $updatedWebsite = $website->withLabel($newWebsiteLabel);
        $updatedBookingInfo = $bookingInfo->withWebsite($updatedWebsite);

        $withoutWebsite = $updatedBookingInfo->withoutWebsite();

        $this->assertNotEquals($bookingInfo, $updatedBookingInfo);
        $this->assertNotEquals($updatedBookingInfo, $withoutWebsite);

        $this->assertEquals($website, $bookingInfo->getWebsite());
        $this->assertEquals($updatedWebsite, $updatedBookingInfo->getWebsite());
        $this->assertNull($withoutWebsite->getWebsite());
    }

    /**
     * @test
     */
    public function it_should_return_copy_with_an_updated_telephone_number(): void
    {
        $telephoneNumber = $this->getTelephoneNumber();
        $bookingInfo = $this->getBookingInfo();

        $updatedTelephoneNumber = new TelephoneNumber('055/555555');
        $updatedBookingInfo = $bookingInfo->withTelephoneNumber($updatedTelephoneNumber);

        $withoutTelephoneNumber = $updatedBookingInfo->withoutTelephoneNumber();

        $this->assertNotEquals($bookingInfo, $updatedBookingInfo);
        $this->assertNotEquals($updatedBookingInfo, $withoutTelephoneNumber);

        $this->assertEquals($telephoneNumber, $bookingInfo->getTelephoneNumber());
        $this->assertEquals($updatedTelephoneNumber, $updatedBookingInfo->getTelephoneNumber());
        $this->assertNull($withoutTelephoneNumber->getTelephoneNumber());
    }

    /**
     * @test
     */
    public function it_should_return_copy_with_an_updated_email_address(): void
    {
        $emailAddress = $this->getEmailAddress();
        $bookingInfo = $this->getBookingInfo();

        $updatedEmailAddress = new EmailAddress('test2@foo.com');
        $updatedBookingInfo = $bookingInfo->withEmailAddress($updatedEmailAddress);

        $withoutEmailAddress = $updatedBookingInfo->withoutEmailAddress();

        $this->assertNotEquals($bookingInfo, $updatedBookingInfo);
        $this->assertNotEquals($updatedBookingInfo, $withoutEmailAddress);

        $this->assertEquals($emailAddress, $bookingInfo->getEmailAddress());
        $this->assertEquals($updatedEmailAddress, $updatedBookingInfo->getEmailAddress());
        $this->assertNull($withoutEmailAddress->getEmailAddress());
    }

    /**
     * @test
     */
    public function it_should_return_copy_with_an_updated_availability(): void
    {
        $availability = $this->getAvailability();
        $bookingInfo = $this->getBookingInfo();

        $from = DateTimeFactory::fromFormat('d-m-Y', '02-01-2018');
        $to = DateTimeFactory::fromFormat('d-m-Y', '19-01-2018');
        $updatedAvailability = BookingAvailability::fromTo($from, $to);
        $updatedBookingInfo = $bookingInfo->withAvailability($updatedAvailability);

        $withoutAvailability = $updatedBookingInfo->withoutAvailability();

        $this->assertNotEquals($bookingInfo, $updatedBookingInfo);
        $this->assertNotEquals($updatedBookingInfo, $withoutAvailability);

        $this->assertEquals($availability, $bookingInfo->getAvailability());
        $this->assertEquals($updatedAvailability, $updatedBookingInfo->getAvailability());
        $this->assertNull($withoutAvailability->getAvailability());
    }

    /**
     * @return Url
     */
    private function getUrl()
    {
        return new Url('http://google.com');
    }

    /**
     * @return TranslatedWebsiteLabel
     */
    private function getWebsiteLabel()
    {
        return new TranslatedWebsiteLabel(
            new Language('nl'),
            new WebsiteLabel('Google')
        );
    }

    /**
     * @return WebsiteLink
     */
    private function getWebsiteLink()
    {
        return new WebsiteLink($this->getUrl(), $this->getWebsiteLabel());
    }

    /**
     * @return TelephoneNumber
     */
    private function getTelephoneNumber()
    {
        return new TelephoneNumber('+3244/444444');
    }

    /**
     * @return EmailAddress
     */
    private function getEmailAddress()
    {
        return new EmailAddress('test@foo.com');
    }

    /**
     * @return BookingAvailability
     */
    private function getAvailability()
    {
        $from = DateTimeFactory::fromFormat('d-m-Y', '01-01-2018');
        $to = DateTimeFactory::fromFormat('d-m-Y', '18-01-2018');
        return BookingAvailability::fromTo($from, $to);
    }

    /**
     * @return BookingInfo
     */
    private function getBookingInfo()
    {
        return new BookingInfo(
            $this->getWebsiteLink(),
            $this->getTelephoneNumber(),
            $this->getEmailAddress(),
            $this->getAvailability()
        );
    }
}
