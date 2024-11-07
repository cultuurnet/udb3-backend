<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use CultuurNet\UDB3\Model\ValueObject\Contact\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumber;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language as Udb3ModelLanguage;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\TranslatedWebsiteLabel;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Model\ValueObject\Web\WebsiteLabel;
use CultuurNet\UDB3\Model\ValueObject\Web\WebsiteLink;
use CultuurNet\UDB3\ValueObject\MultilingualString;
use PHPUnit\Framework\TestCase;

class BookingInfoTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_compare(): void
    {
        $bookingInfo = new BookingInfo(
            'www.publiq.be',
            new MultilingualString(
                new Udb3ModelLanguage('nl'),
                'publiq'
            ),
            '02 123 45 67',
            'info@publiq.be'
        );

        $sameBookingInfo = new BookingInfo(
            'www.publiq.be',
            new MultilingualString(
                new Udb3ModelLanguage('nl'),
                'publiq'
            ),
            '02 123 45 67',
            'info@publiq.be'
        );

        $otherBookingInfo = new BookingInfo(
            'www.2dotstwice.be',
            new MultilingualString(
                new Udb3ModelLanguage('nl'),
                '2dotstwice'
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
    public function it_should_be_creatable_from_a_complete_udb3_model_booking_info(): void
    {
        $udb3ModelBookingInfo = new \CultuurNet\UDB3\Model\ValueObject\Contact\BookingInfo(
            new WebsiteLink(
                new Url('https://publiq.be'),
                new TranslatedWebsiteLabel(
                    new Udb3ModelLanguage('nl'),
                    new WebsiteLabel('publiq')
                )
            ),
            new TelephoneNumber('044/444444'),
            new EmailAddress('info@publiq.be'),
            new BookingAvailability(
                DateTimeFactory::fromAtom('2018-01-01T00:00:00+01:00'),
                DateTimeFactory::fromAtom('2018-01-10T00:00:00+01:00')
            )
        );

        $expected = new BookingInfo(
            'https://publiq.be',
            new MultilingualString(
                new Udb3ModelLanguage('nl'),
                'publiq'
            ),
            '044/444444',
            'info@publiq.be',
            DateTimeFactory::fromAtom('2018-01-01T00:00:00+01:00'),
            DateTimeFactory::fromAtom('2018-01-10T00:00:00+01:00')
        );

        $actual = BookingInfo::fromUdb3ModelBookingInfo($udb3ModelBookingInfo);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_be_creatable_from_an_empty_udb3_model_booking_info(): void
    {
        $udb3ModelBookingInfo = new \CultuurNet\UDB3\Model\ValueObject\Contact\BookingInfo();

        $expected = new BookingInfo();
        $actual = BookingInfo::fromUdb3ModelBookingInfo($udb3ModelBookingInfo);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_return_null_for_empty_properties(): void
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
    public function it_can_serialize_and_deserialize_partial_booking_info(): void
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
    public function it_ignores_obsolete_properties_when_deserializing(): void
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
                new Udb3ModelLanguage('nl'),
                'publiq'
            ),
            '044/444444',
            'info@publiq.be',
            DateTimeFactory::fromAtom('2018-01-01T00:00:00+01:00'),
            DateTimeFactory::fromAtom('2018-01-14T23:59:59+01:00')
        );

        $actual = BookingInfo::deserialize($data);

        $this->assertEquals($expected, $actual);
    }
}
