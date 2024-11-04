<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\JSONLD;

use CultuurNet\UDB3\Calendar\CalendarFactoryInterface;
use CultuurNet\UDB3\Cdb\Description\MergedDescription;
use CultuurNet\UDB3\Cdb\CdbXMLToJsonLDLabelImporter;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\CdbXmlContactInfoImporterInterface;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\CdbXMLItemBaseImporter;

/**
 * Takes care of importing actors in the CdbXML format (UDB2) that represent
 * a place, into a UDB3 JSON-LD document.
 */
class CdbXMLImporter
{
    private CdbXMLItemBaseImporter $cdbXMLItemBaseImporter;

    private CalendarFactoryInterface $calendarFactory;

    private CdbXmlContactInfoImporterInterface $cdbXmlContactInfoImporter;

    private CdbXMLToJsonLDLabelImporter $cdbXmlLabelImporter;


    public function __construct(
        CdbXMLItemBaseImporter $dbXMLItemBaseImporter,
        CalendarFactoryInterface $calendarFactory,
        CdbXmlContactInfoImporterInterface $cdbXmlContactInfoImporter,
        CdbXMLToJsonLDLabelImporter $cdbXmlLabelImporter
    ) {
        $this->cdbXMLItemBaseImporter = $dbXMLItemBaseImporter;
        $this->calendarFactory = $calendarFactory;
        $this->cdbXmlContactInfoImporter = $cdbXmlContactInfoImporter;
        $this->cdbXmlLabelImporter = $cdbXmlLabelImporter;
    }

    public function documentWithCdbXML(
        \stdClass $base,
        \CultureFeed_Cdb_Item_Actor $item
    ): \stdClass {
        $jsonLD = clone $base;

        $detail = null;

        /** @var \CultureFeed_Cdb_Data_ActorDetail[] $details */
        $details = $item->getDetails();

        foreach ($details as $languageDetail) {
            // The first language detail found will be used to retrieve
            // properties from which in UDB3 are not any longer considered
            // to be language specific.
            if (!$detail) {
                $detail = $languageDetail;
            }
        }

        // make sure the description is an object as well before trying to add
        // translations
        if (empty($jsonLD->description)) {
            $jsonLD->description = new \stdClass();
        }

        try {
            $description = MergedDescription::fromCdbDetail($detail);
            $jsonLD->description->nl = $description->toString();
        } catch (\InvalidArgumentException $e) {
            // No description found.
        }

        // make sure the name is an object as well before trying to add
        // translations
        if (empty($jsonLD->name)) {
            $jsonLD->name = new \stdClass();
        }
        $jsonLD->name->nl = $detail->getTitle();

        $this->cdbXMLItemBaseImporter->importPublicationInfo($item, $jsonLD);
        $this->cdbXMLItemBaseImporter->importAvailable($item, $jsonLD);
        $this->cdbXMLItemBaseImporter->importExternalId($item, $jsonLD);
        $this->cdbXMLItemBaseImporter->importWorkflowStatus($item, $jsonLD);

        // Address
        $contact_cdb = $item->getContactInfo();
        if ($contact_cdb) {
            /** @var \CultureFeed_Cdb_Data_Address[] $addresses */
            $addresses = $contact_cdb->getAddresses();

            foreach ($addresses as $address) {
                /** @var \CultureFeed_Cdb_Data_Address_PhysicalAddress|null $physicalAddress */
                $physicalAddress = $address->getPhysicalAddress();

                if ($physicalAddress) {
                    if (!isset($jsonLD->address)) {
                        $jsonLD->address = new \stdClass();
                    }

                    $jsonLD->address->nl = [
                        'addressCountry' => $physicalAddress->getCountry(),
                        'addressLocality' => $physicalAddress->getCity(),
                        'postalCode' => $physicalAddress->getZip(),
                        'streetAddress' =>
                            $physicalAddress->getStreet() . ' ' .
                            $physicalAddress->getHouseNumber(),
                    ];

                    break;
                }
            }
        }

        $jsonLD->typicalAgeRange = '-';

        if ($item->getContactInfo()) {
            $this->cdbXmlContactInfoImporter->importBookingInfo(
                $jsonLD,
                $item->getContactInfo(),
                $detail->getPrice(),
                null
            );

            $this->cdbXmlContactInfoImporter->importContactPoint(
                $jsonLD,
                $item->getContactInfo()
            );
        }

        $this->cdbXmlLabelImporter->importLabels($item, $jsonLD);

        $this->importTerms($item, $jsonLD);

        if ($item instanceof \CultureFeed_Cdb_Item_Actor) {
            $calendar = $this->calendarFactory->createFromWeekScheme(
                $item->getWeekScheme()
            );
            $jsonLD = (object)array_merge((array)$jsonLD, $calendar->toJsonLd());
        }

        return $jsonLD;
    }

    private function importTerms(\CultureFeed_Cdb_Item_Base $actor, \stdClass $jsonLD): void
    {
        $themeBlacklist = [];
        $categories = [];
        foreach ($actor->getCategories() as $category) {
            /* @var \Culturefeed_Cdb_Data_Category $category */
            if ($category && !in_array($category->getName(), $themeBlacklist)) {
                $categories[] = [
                    'label' => $category->getName(),
                    'domain' => $category->getType(),
                    'id' => $category->getId(),
                ];
            }
        }
        $jsonLD->terms = $categories;
    }
}
