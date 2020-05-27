<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Place\ReadModel\JSONLD;

use CultuurNet\UDB3\CalendarFactoryInterface;
use CultuurNet\UDB3\Cdb\Description\MergedDescription;
use CultuurNet\UDB3\LabelImporter;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\CdbXmlContactInfoImporterInterface;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\CdbXMLItemBaseImporter;

/**
 * Takes care of importing actors in the CdbXML format (UDB2) that represent
 * a place, into a UDB3 JSON-LD document.
 */
class CdbXMLImporter
{
    /**
     * @var CdbXMLItemBaseImporter
     */
    private $cdbXMLItemBaseImporter;

    /**
     * @var CalendarFactoryInterface
     */
    private $calendarFactory;

    /**
     * @var CdbXmlContactInfoImporterInterface
     */
    private $cdbXmlContactInfoImporter;

    /**
     * @param CdbXMLItemBaseImporter $dbXMLItemBaseImporter
     * @param CalendarFactoryInterface $calendarFactory
     * @param CdbXmlContactInfoImporterInterface $cdbXmlContactInfoImporter
     */
    public function __construct(
        CdbXMLItemBaseImporter $dbXMLItemBaseImporter,
        CalendarFactoryInterface $calendarFactory,
        CdbXmlContactInfoImporterInterface $cdbXmlContactInfoImporter
    ) {
        $this->cdbXMLItemBaseImporter = $dbXMLItemBaseImporter;
        $this->calendarFactory = $calendarFactory;
        $this->cdbXmlContactInfoImporter = $cdbXmlContactInfoImporter;
    }

    /**
     * Imports a UDB2 organizer actor into a UDB3 JSON-LD document.
     *
     * @param \stdClass                   $base
     *   The JSON-LD document object to start from.
     * @param \CultureFeed_Cdb_Item_Base|\CultureFeed_Cdb_Item_Actor $item
     *   The event/actor data from UDB2 to import.
     *
     * @return \stdClass
     *   A new JSON-LD document object with the UDB2 actor data merged in.
     */
    public function documentWithCdbXML(
        $base,
        \CultureFeed_Cdb_Item_Base $item
    ) {
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
            $jsonLD->description->nl = $description->toNative();
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
                $address = $address->getPhysicalAddress();

                if ($address) {
                    if (!isset($jsonLD->address)) {
                        $jsonLD->address = new \stdClass();
                    }

                    $jsonLD->address->nl = array(
                        'addressCountry' => $address->getCountry(),
                        'addressLocality' => $address->getCity(),
                        'postalCode' => $address->getZip(),
                        'streetAddress' =>
                            $address->getStreet() . ' ' .
                            $address->getHouseNumber(),
                    );

                    break;
                }
            }
        }

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

        $labelImporter = new LabelImporter();
        $labelImporter->importLabels($item, $jsonLD);

        $this->importTerms($item, $jsonLD);

        if ($item instanceof \CultureFeed_Cdb_Item_Actor) {
            $calendar = $this->calendarFactory->createFromWeekScheme(
                $item->getWeekScheme()
            );
            $jsonLD = (object)array_merge((array)$jsonLD, $calendar->toJsonLd());
        }

        return $jsonLD;
    }

    public function eventDocumentWithCdbXML(
        $base,
        \CultureFeed_Cdb_Item_Base $item
    ) {
        $jsonLD = $this->documentWithCdbXML($base, $item);

        return $jsonLD;
    }

    /**
     * @param \CultureFeed_Cdb_Item_Base $actor
     * @param \stdClass $jsonLD
     */
    private function importTerms(\CultureFeed_Cdb_Item_Base $actor, $jsonLD)
    {
        $themeBlacklist = [];
        $categories = array();
        foreach ($actor->getCategories() as $category) {
            /* @var \Culturefeed_Cdb_Data_Category $category */
            if ($category && !in_array($category->getName(), $themeBlacklist)) {
                $categories[] = array(
                    'label' => $category->getName(),
                    'domain' => $category->getType(),
                    'id' => $category->getId(),
                );
            }
        }
        $jsonLD->terms = $categories;
    }
}
