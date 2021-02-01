<?php

namespace CultuurNet\UDB3;

use CultuurNet\UDB3\Model\ValueObject\Contact\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumber;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\TranslatedWebsiteLabel;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Model\ValueObject\Web\WebsiteLabel;
use CultuurNet\UDB3\Model\ValueObject\Web\WebsiteLink;
use CultuurNet\UDB3\ValueObject\MultilingualString;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class BookingInfoTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_compare()
    {
        $bookingInfo = new BookingInfo(
            'www.publiq.be',
            new MultilingualString(
                new Language('nl'),
                new StringLiteral('publiq')
            ),
            '02 123 45 67',
            'info@publiq.be'
        );

        $sameBookingInfo = new BookingInfo(
            'www.publiq.be',
            new MultilingualString(
                new Language('nl'),
                new StringLiteral('publiq')
            ),
            '02 123 45 67',
            'info@publiq.be'
        );

        $otherBookingInfo = new BookingInfo(
            'www.2dotstwice.be',
            new MultilingualString(
                new Language('nl'),
                new StringLiteral('2dotstwice')
            ),
            '016 12 34 56',
            'info@2dotstwice.be'
        );

        $this->assertTrue($bookingInfo->sameAs($sameBookingInfo));
        $this->assertFalse($bookingInfo->sameAs($otherBookingInfo));
    }

    /**
     * @test
     */
    public function it_should_be_creatable_from_a_complete_udb3_model_booking_info()
    {
        $udb3ModelBookingInfo = new \CultuurNet\UDB3\Model\ValueObject\Contact\BookingInfo(
            new WebsiteLink(
                new Url('https://publiq.be'),
                new TranslatedWebsiteLabel(
                    new \CultuurNet\UDB3\Model\ValueObject\Translation\Language('nl'),
                    new WebsiteLabel('publiq')
                )
            ),
            new TelephoneNumber('044/444444'),
            new EmailAddress('info@publiq.be'),
            new BookingAvailability(
                \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T00:00:00+01:00'),
                \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-10T00:00:00+01:00')
            )
        );

        $expected = new BookingInfo(
            'https://publiq.be',
            new MultilingualString(
                new Language('nl'),
                new StringLiteral('publiq')
            ),
            '044/444444',
            'info@publiq.be',
            \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T00:00:00+01:00'),
            \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-10T00:00:00+01:00')
        );

        $actual = BookingInfo::fromUdb3ModelBookingInfo($udb3ModelBookingInfo);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_be_creatable_from_an_empty_udb3_model_booking_info()
    {
        $udb3ModelBookingInfo = new \CultuurNet\UDB3\Model\ValueObject\Contact\BookingInfo();

        $expected = new BookingInfo();
        $actual = BookingInfo::fromUdb3ModelBookingInfo($udb3ModelBookingInfo);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_return_null_for_empty_properties()
    {
        $bookingInfo = new BookingInfo();

        $bookingInfoWithEmptyString = new BookingInfo(
            '',
            null,
            '',
            '',
            null,
            null
        );

        $deserialized = BookingInfo::deserialize([]);

        $expectedSerialized = [];
        $expectedJson = [];

        $this->assertNull($bookingInfo->getUrl());
        $this->assertNull($bookingInfo->getUrlLabel());
        $this->assertNull($bookingInfo->getPhone());
        $this->assertNull($bookingInfo->getEmail());
        $this->assertNull($bookingInfo->getAvailabilityStarts());
        $this->assertNull($bookingInfo->getAvailabilityEnds());
        $this->assertEquals($expectedSerialized, $bookingInfo->serialize());
        $this->assertEquals($expectedJson, $bookingInfo->toJsonLd());

        $this->assertNull($bookingInfoWithEmptyString->getUrl());
        $this->assertNull($bookingInfoWithEmptyString->getUrlLabel());
        $this->assertNull($bookingInfoWithEmptyString->getPhone());
        $this->assertNull($bookingInfoWithEmptyString->getEmail());
        $this->assertNull($bookingInfoWithEmptyString->getAvailabilityStarts());
        $this->assertNull($bookingInfoWithEmptyString->getAvailabilityEnds());
        $this->assertEquals($expectedSerialized, $bookingInfoWithEmptyString->serialize());
        $this->assertEquals($expectedJson, $bookingInfoWithEmptyString->toJsonLd());

        $this->assertNull($deserialized->getUrl());
        $this->assertNull($deserialized->getUrlLabel());
        $this->assertNull($deserialized->getPhone());
        $this->assertNull($deserialized->getEmail());
        $this->assertNull($deserialized->getAvailabilityStarts());
        $this->assertNull($deserialized->getAvailabilityEnds());
        $this->assertEquals($expectedSerialized, $deserialized->serialize());
        $this->assertEquals($expectedJson, $deserialized->toJsonLd());
    }

    /**
     * @test
     */
    public function it_can_serialize_and_deserialize_partial_booking_info()
    {
        $phone = '044/444444';
        $email = 'info@publiq.be';

        $original = new BookingInfo(
            null,
            null,
            $phone,
            $email,
            null,
            null
        );

        $expectedSerialized = [
            'phone' => $phone,
            'email' => $email,
        ];

        $serialized = $original->serialize();
        $deserialized = BookingInfo::deserialize($serialized);

        $this->assertEquals($expectedSerialized, $serialized);
        $this->assertEquals($original, $deserialized);
    }

    /**
     * @test
     */
    public function it_ignores_obsolete_properties_when_deserializing()
    {
        $data = [
            'url' => 'https://www.publiq.be',
            'urlLabel' => ['nl' => 'publiq'],
            'phone' => '044/444444',
            'email' => 'info@publiq.be',
            'availabilityStarts' => '2018-01-01T00:00:00+01:00',
            'availabilityEnds' => '2018-01-14T23:59:59+01:00',
            'name' => 'Naam',
            'description' => 'Lorem ipsum',
            'price' => 100,
            'priceCurrency' => 'EUR',
        ];

        $expected = new BookingInfo(
            'https://www.publiq.be',
            new MultilingualString(
                new Language('nl'),
                new StringLiteral('publiq')
            ),
            '044/444444',
            'info@publiq.be',
            \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T00:00:00+01:00'),
            \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-14T23:59:59+01:00')
        );

        $actual = BookingInfo::deserialize($data);

        $this->assertEquals($expected, $actual);
    }
}
