<?php

namespace CultuurNet\UDB3\Offer\ReadModel\JSONLD;

interface CdbXmlContactInfoImporterInterface
{
    /**
     * @param \CultureFeed_Cdb_Data_ContactInfo $contactInfo ,
     * @return void
     */
    public function importBookingInfo(
        \stdClass $jsonLD,
        \CultureFeed_Cdb_Data_ContactInfo $contactInfo,
        \CultureFeed_Cdb_Data_Price $price = null,
        \CultureFeed_Cdb_Data_Calendar_BookingPeriod $bookingPeriod = null
    );

    /**
     * @return void
     */
    public function importContactPoint(
        \stdClass $jsonLD,
        \CultureFeed_Cdb_Data_ContactInfo $contactInfo
    );
}
