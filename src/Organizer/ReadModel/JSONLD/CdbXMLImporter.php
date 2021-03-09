<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\ReadModel\JSONLD;

use CultureFeed_Cdb_Data_Address_PhysicalAddress;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\LabelImporter;
use stdClass;
use ValueObjects\Geography\Country;

/**
 * Takes care of importing actors in the CdbXML format (UDB2) that represent
 * an organizer, into a UDB3 JSON-LD document.
 */
class CdbXMLImporter
{
    /**
     * Imports a UDB2 organizer actor into a UDB3 JSON-LD document.
     *
     * @param stdClass $base
     *   The JSON-LD document object to start from.
     * @param \CultureFeed_Cdb_Item_Actor $actor
     *   The actor data from UDB2 to import.
     *
     * @return stdClass
     *   A new JSON-LD document object with the UDB2 actor data merged in.
     */
    public function documentWithCdbXML(
        $base,
        \CultureFeed_Cdb_Item_Actor $actor
    ) {
        $jsonLD = clone $base;

        $detail = null;

        /** @var \CultureFeed_Cdb_Data_Detail[] $details */
        $details = $actor->getDetails();

        if (empty($jsonLD->name)) {
            $jsonLD->name = new stdClass();
        }

        foreach ($details as $languageDetail) {
            // The first language detail found will be used to retrieve
            // properties from which in UDB3 are not any longer considered
            // to be language specific.
            if (!$detail) {
                $detail = $languageDetail;
            }

            $jsonLD->name->{$detail->getLanguage()} = $detail->getTitle();
        }

        $jsonLD->address = new stdClass();
        $cdbContact = $actor->getContactInfo();
        if ($cdbContact) {
            /** @var \CultureFeed_Cdb_Data_Address[] $addresses * */
            $addresses = $cdbContact->getAddresses();

            foreach ($addresses as $address) {
                /** @var CultureFeed_Cdb_Data_Address_PhysicalAddress|null $physicalAddress */
                $physicalAddress = $address->getPhysicalAddress();

                if ($physicalAddress) {
                    $physicalAddress = new Address(
                        new Street($physicalAddress->getStreet() . ' ' . $physicalAddress->getHouseNumber()),
                        new PostalCode($physicalAddress->getZip()),
                        new Locality($physicalAddress->getCity()),
                        Country::fromNative($physicalAddress->getCountry())
                    );

                    $jsonLD->address->{$jsonLD->mainLanguage} = $physicalAddress->toJsonLd();
                }
            }

            $emails = [];
            $phones = [];
            $urls = [];

            /* @var \CultureFeed_Cdb_Data_Mail[] $cdbEmails */
            $cdbEmails = $cdbContact->getMails();
            foreach ($cdbEmails as $mail) {
                $emails[] = $mail->getMailAddress();
            }

            /* @var \CultureFeed_Cdb_Data_Phone[] $cdbPhones */
            $cdbPhones = $cdbContact->getPhones();
            foreach ($cdbPhones as $phone) {
                $phones[] = $phone->getNumber();
            }

            /* @var \CultureFeed_Cdb_Data_Url[] $cdbUrls */
            $cdbUrls = $cdbContact->getUrls();
            foreach ($cdbUrls as $url) {
                $urls[] = $url->getUrl();
            }

            $labelImporter = new LabelImporter();
            $labelImporter->importLabels($actor, $jsonLD);

            $contactPoint = new ContactPoint($phones, $emails, $urls);

            if (!$contactPoint->sameAs(new ContactPoint())) {
                $jsonLD->contactPoint = $contactPoint->toJsonLd();
            }
        }

        return $jsonLD;
    }
}
