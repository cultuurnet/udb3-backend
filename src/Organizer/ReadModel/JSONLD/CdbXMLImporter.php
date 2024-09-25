<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\ReadModel\JSONLD;

use CultureFeed_Cdb_Data_Address_PhysicalAddress;
use CultuurNet\UDB3\Address\CultureFeed\CultureFeedAddressFactoryInterface;
use CultuurNet\UDB3\Cdb\CdbXMLToJsonLDLabelImporter;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Contact\ContactPointNormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Geography\AddressNormalizer;
use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumber;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumbers;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Model\ValueObject\Web\Urls;
use stdClass;

class CdbXMLImporter
{
    private CdbXMLToJsonLDLabelImporter $labelImporter;

    private CultureFeedAddressFactoryInterface $addressFactory;

    public function __construct(
        CdbXMLToJsonLDLabelImporter $labelImporter,
        CultureFeedAddressFactoryInterface $addressFactory
    ) {
        $this->labelImporter = $labelImporter;
        $this->addressFactory = $addressFactory;
    }

    public function documentWithCdbXML(
        stdClass $base,
        \CultureFeed_Cdb_Item_Actor $actor
    ): stdClass {
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
                    $physicalAddress = $this->addressFactory->fromCdbAddress($physicalAddress);
                    $jsonLD->address->{$jsonLD->mainLanguage} = (new AddressNormalizer())->normalize($physicalAddress);
                }
            }

            $emails = new EmailAddresses();
            $phones = new TelephoneNumbers();
            $urls = new Urls();

            /* @var \CultureFeed_Cdb_Data_Mail[] $cdbEmails */
            $cdbEmails = $cdbContact->getMails();
            foreach ($cdbEmails as $mail) {
                $emails = $emails->with(new EmailAddress($mail->getMailAddress()));
            }

            /* @var \CultureFeed_Cdb_Data_Phone[] $cdbPhones */
            $cdbPhones = $cdbContact->getPhones();
            foreach ($cdbPhones as $phone) {
                $phones = $phones->with(new TelephoneNumber($phone->getNumber()));
            }

            /* @var \CultureFeed_Cdb_Data_Url[] $cdbUrls */
            $cdbUrls = $cdbContact->getUrls();
            foreach ($cdbUrls as $url) {
                $urls = $urls->with(new Url($url->getUrl()));
            }

            $this->labelImporter->importLabels($actor, $jsonLD);

            $contactPoint = new ContactPoint($phones, $emails, $urls);

            if (!$contactPoint->sameAs(new ContactPoint())) {
                $jsonLD->contactPoint = (new ContactPointNormalizer())->normalize($contactPoint);
            }
        }

        return $jsonLD;
    }
}
