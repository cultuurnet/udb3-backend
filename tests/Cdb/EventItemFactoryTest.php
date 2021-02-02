<?php

namespace CultuurNet\UDB3\Cdb;

use PHPUnit\Framework\TestCase;

class EventItemFactoryTest extends TestCase
{
    /**
     * @var EventItemFactory
     */
    private $factory;

    public function setUp()
    {
        $this->factory = new EventItemFactory(
            \CultureFeed_Cdb_Xml::namespaceUriForVersion('3.3')
        );
    }

    /**
     * @test
     */
    public function it_creates_an_event_object_from_cdbxml()
    {
        $expected = new \CultureFeed_Cdb_Item_Event();
        $expected->setCdbId('d53c2bc9-8f0e-4c9a-8457-77e8b3cab3d1');

        $details = new \CultureFeed_Cdb_Data_EventDetailList();
        $nlDetails = new \CultureFeed_Cdb_Data_EventDetail();
        $nlDetails->setLanguage('nl');
        $nlDetails->setTitle('Ruime Activiteit');
        $nlDetails->setShortDescription('KB');
        $nlDetails->setCalendarSummary('vrij 31/01/14 van 12:00 tot 15:00 do 20/02/14 van 12:00 tot 15:00');
        $details->add($nlDetails);
        $expected->setDetails($details);

        $categories = new \CultureFeed_Cdb_Data_CategoryList();
        $categories->add(
            new \CultureFeed_Cdb_Data_Category('theme', '1.7.6.0.0', 'Griezelfilm of horror')
        );
        $expected->setCategories($categories);

        $calendar = new \CultureFeed_Cdb_Data_Calendar_TimestampList();
        $calendar->add(
            new \CultureFeed_Cdb_Data_Calendar_Timestamp(
                '2014-01-31',
                '12:00:00',
                '15:00:00'
            )
        );
        $expected->setCalendar($calendar);

        $physicalAddress = new \CultureFeed_Cdb_Data_Address_PhysicalAddress();
        $physicalAddress->setStreet('Sint-Gislainstraat');
        $physicalAddress->setHouseNumber('62');
        $physicalAddress->setZip('1000');
        $physicalAddress->setCity('Brussel');
        $physicalAddress->setCountry('BE');
        $address = new \CultureFeed_Cdb_Data_Address($physicalAddress);

        $contactInformation = new \CultureFeed_Cdb_Data_ContactInfo();
        $contactInformation->addAddress($address);
        $contactInformation->addMail(
            new \CultureFeed_Cdb_Data_Mail('jonas@cnet.be')
        );
        $contactInformation->addPhone(
            new \CultureFeed_Cdb_Data_Phone('+32 555 555')
        );
        $contactInformation->addUrl(
            new \CultureFeed_Cdb_Data_Url('http://www.test.com')
        );
        $expected->setContactInfo($contactInformation);

        $location = new \CultureFeed_Cdb_Data_Location($address);
        $expected->setLocation($location);

        $cdbXml = file_get_contents(__DIR__ . '/samples/event.xml');

        $this->assertEquals(
            $expected,
            $this->factory->createFromCdbXml(
                $cdbXml
            )
        );
    }
}
