<?php

namespace CultuurNet\UDB3\Offer\ReadModel\JSONLD;

use PHPUnit\Framework\TestCase;

class CdbXmlContactInfoImporterTest extends TestCase
{
    /**
     * @var CdbXmlContactInfoImporter
     */
    private $cdbXmlContactInfoImporter;

    /**
     * @var \CultureFeed_Cdb_Data_ContactInfo
     */
    private $cdbContactInfo;

    protected function setUp()
    {
        $this->cdbXmlContactInfoImporter = new CdbXmlContactInfoImporter();

        $this->cdbContactInfo = $this->createCdbContactInfo();
    }

    /**
     * @test
     */
    public function it_converts_contact_info_to_booking_info_json()
    {
        $jsonLd = new \StdClass();

        $this->cdbXmlContactInfoImporter->importBookingInfo(
            $jsonLd,
            $this->cdbContactInfo,
            null,
            null
        );

        $expectedBookingInfo = [
            'email' => 'info@2dotstwice.be',
            'phone' => '987654321',
            'url' => 'www.2dotstwice.be',
            'urlLabel' => ['nl' => 'Reserveer plaatsen'],
        ];

        $this->assertEquals($expectedBookingInfo, $jsonLd->bookingInfo);
    }

    /**
     * @test
     */
    public function it_converts_price_to_booking_info_json()
    {
        $cdbPrice = new \CultureFeed_Cdb_Data_Price();
        $cdbPrice->setDescription('Prijs voor volwassen.');
        $cdbPrice->setTitle('Volwassen.');
        $cdbPrice->setValue(9.99);

        $cdbBookingPeriod = new \CultureFeed_Cdb_Data_Calendar_BookingPeriod(
            1483258210,
            1483464325
        );

        $jsonLd = new \StdClass();

        $this->cdbXmlContactInfoImporter->importBookingInfo(
            $jsonLd,
            new \CultureFeed_Cdb_Data_ContactInfo(),
            $cdbPrice,
            $cdbBookingPeriod
        );

        $expectedBookingInfo = [
            'description' => 'Prijs voor volwassen.',
            'name' => 'Volwassen.',
            'price' => 9.99,
            'priceCurrency' => 'EUR',
            'availabilityStarts' => '2017-01-01T08:10:10+00:00',
            'availabilityEnds' => '2017-01-03T17:25:25+00:00',
        ];

        $this->assertEquals($expectedBookingInfo, $jsonLd->bookingInfo);
    }

    /**
     * @test
     */
    public function it_converts_contact_info_to_contact_point_json()
    {
        $jsonLd = new \StdClass();

        $this->cdbXmlContactInfoImporter->importContactPoint(
            $jsonLd,
            $this->cdbContactInfo
        );

        $expectedContactPoint = [
            'email' => [
                'info@cultuurnet.be',
                'info@gmail.com',
            ],
            'phone' => [
                '89898989',
                '12121212',
            ],
            'url' => [
                'www.cultuurnet.be',
                'www.booking.com',
            ],
        ];

        $this->assertEquals($expectedContactPoint, $jsonLd->contactPoint);
    }

    /**
     * @return \CultureFeed_Cdb_Data_ContactInfo
     */
    private function createCdbContactInfo()
    {
        $contactInfo = new \CultureFeed_Cdb_Data_ContactInfo();

        $contactInfo->addMail(
            new \CultureFeed_Cdb_Data_Mail(
                'info@cultuurnet.be',
                false,
                false
            )
        );
        $contactInfo->addMail(
            new \CultureFeed_Cdb_Data_Mail(
                'info@2dotstwice.be',
                false,
                true
            )
        );
        $contactInfo->addMail(
            new \CultureFeed_Cdb_Data_Mail(
                'info@gmail.com',
                false,
                false
            )
        );

        $contactInfo->addPhone(
            new \CultureFeed_Cdb_Data_Phone(
                '89898989',
                'mobile',
                false,
                false
            )
        );
        $contactInfo->addPhone(
            new \CultureFeed_Cdb_Data_Phone(
                '987654321',
                'mobile',
                false,
                true
            )
        );
        $contactInfo->addPhone(
            new \CultureFeed_Cdb_Data_Phone(
                '12121212',
                'phone',
                false,
                false
            )
        );

        $contactInfo->addUrl(
            new \CultureFeed_Cdb_Data_Url(
                'www.cultuurnet.be',
                false,
                false
            )
        );
        $contactInfo->addUrl(
            new \CultureFeed_Cdb_Data_Url(
                'www.2dotstwice.be',
                false,
                true
            )
        );
        $contactInfo->addUrl(
            new \CultureFeed_Cdb_Data_Url(
                'www.booking.com',
                false,
                false
            )
        );

        return $contactInfo;
    }
}
