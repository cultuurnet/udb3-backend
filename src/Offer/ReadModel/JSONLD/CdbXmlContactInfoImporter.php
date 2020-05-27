<?php

namespace CultuurNet\UDB3\Offer\ReadModel\JSONLD;

use CultuurNet\UDB3\ReadModel\MultilingualJsonLDProjectorTrait;

class CdbXmlContactInfoImporter implements CdbXmlContactInfoImporterInterface
{
    use MultilingualJsonLDProjectorTrait;

    /**
     * @inheritdoc
     */
    public function importBookingInfo(
        \stdClass $jsonLD,
        \CultureFeed_Cdb_Data_ContactInfo $contactInfo,
        \CultureFeed_Cdb_Data_Price $price = null,
        \CultureFeed_Cdb_Data_Calendar_BookingPeriod $bookingPeriod = null
    ) {
        $bookingInfo = array();

        if ($price) {
            if ($price->getDescription()) {
                $bookingInfo['description'] = $price->getDescription();
            }
            if ($price->getTitle()) {
                $bookingInfo['name'] = $price->getTitle();
            }
            if ($price->getValue() !== null) {
                $bookingInfo['priceCurrency'] = 'EUR';
                $bookingInfo['price'] = floatval($price->getValue());
            }
            if ($bookingPeriod) {
                $startDate = $this->dateFromUdb2UnixTime($bookingPeriod->getDateFrom());
                $endDate = $this->dateFromUdb2UnixTime($bookingPeriod->getDateTill());

                $bookingInfo['availabilityStarts'] = $startDate->format('c');
                $bookingInfo['availabilityEnds'] = $endDate->format('c');
            }
        }

        // Add reservation contact data.
        /** @var \CultureFeed_Cdb_Data_Url[] $urls */
        $urls = $contactInfo->getUrls();
        foreach ($urls as $url) {
            if ($url->isForReservations()) {
                $bookingInfo['url'] = $url->getUrl();
                break;
            }
        }

        if (array_key_exists('url', $bookingInfo)) {
            $mainLanguage = $this->getMainLanguage($jsonLD)->getCode();
            $bookingInfo['urlLabel'] = [$mainLanguage => 'Reserveer plaatsen'];
        }

        /** @var \CultureFeed_Cdb_Data_Phone[] $phones */
        $phones = $contactInfo->getPhones();
        foreach ($phones as $phone) {
            if ($phone->isForReservations()) {
                $bookingInfo['phone'] = $phone->getNumber();
                break;
            }
        }

        foreach ($contactInfo->getMails() as $mail) {
            if ($mail->isForReservations()) {
                $bookingInfo['email'] = $mail->getMailAddress();
                break;
            }
        }

        if (!empty($bookingInfo)) {
            $jsonLD->bookingInfo = $bookingInfo;
        }
    }

    /**
     * @inheritdoc
     */
    public function importContactPoint(
        \stdClass $jsonLD,
        \CultureFeed_Cdb_Data_ContactInfo $contactInfo
    ) {
        $notForReservations = function ($item) {
            /** @var \CultureFeed_Cdb_Data_Url|\CultureFeed_Cdb_Data_Phone|\CultureFeed_Cdb_Data_Mail $item */
            return !$item->isForReservations();
        };

        $contactPoint = array();

        $emails = array_filter($contactInfo->getMails(), $notForReservations);

        if (!empty($emails)) {
            $contactPoint['email'] = array_map(
                function (\CultureFeed_Cdb_Data_Mail $email) {
                    return $email->getMailAddress();
                },
                $emails
            );
            $contactPoint['email'] = array_values($contactPoint['email']);
        }

        $phones = array_filter($contactInfo->getPhones(), $notForReservations);

        if (!empty($phones)) {
            $contactPoint['phone'] = array_map(
                function (\CultureFeed_Cdb_Data_phone $phone) {
                    return $phone->getNumber();
                },
                $phones
            );
            $contactPoint['phone'] = array_values($contactPoint['phone']);
        }

        $urls = array_filter($contactInfo->getUrls(), $notForReservations);

        if (!empty($urls)) {
            $contactPoint['url'] = array_map(
                function (\CultureFeed_Cdb_Data_Url $url) {
                    return $url->getUrl();
                },
                $urls
            );
            $contactPoint['url'] = array_values($contactPoint['url']);
        }

        array_filter($contactPoint);
        if (!empty($contactPoint)) {
            $jsonLD->contactPoint = $contactPoint;
        }
    }

    /**
     * @param int $unixTime
     * @return \DateTime
     */
    private function dateFromUdb2UnixTime($unixTime)
    {
        $dateTime = new \DateTime(
            '@' . $unixTime,
            new \DateTimeZone('Europe/Brussels')
        );

        return $dateTime;
    }
}
